<?php

declare(strict_types=1);

namespace App\Services\Summary;

use App\Models\Transaction;
use App\Models\TransactionSummary;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Orchestre la génération et la fraîcheur du résumé de transactions.
 *
 * Points d'entrée publics :
 *   - generateBaseline(User)  → régénère depuis toutes les transactions (utilisé par le job mensuel)
 *   - getFreshSummary(User)   → retourne un résumé à jour ; applique l'incrémental si nécessaire
 *
 * Principe : les agrégats sont déterministes et fusionnables par addition (SummaryAggregator).
 * Le LLM n'intervient que pour le narratif (non implémenté ici, prévu pour le chat).
 */
final class TransactionSummaryService
{
    private const CACHE_TTL      = 300;  // secondes (5 min)
    private const LOCK_TIMEOUT   = 30;   // secondes pour obtenir le lock
    private const LOCK_WAIT      = 10;   // secondes max à attendre si le lock est pris
    private const CHUNK_SIZE     = 500;  // transactions par chunk pour éviter les saturation mémoire

    public function __construct(
        private readonly SummaryAggregator $aggregator,
    ) {}

    // ──────────────────────────────────────────────────────────────────────────
    // API PUBLIQUE
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Retourne le résumé à jour, en appliquant l'incrémental si nécessaire.
     *
     * Algorithme :
     *   1. Cache hit → retour immédiat.
     *   2. Aucun résumé en base → baseline à la volée (nouvel utilisateur).
     *   3. Aucune nouvelle transaction → résumé existant renvoyé sans recalcul.
     *   4. Delta détecté → lock par utilisateur → incrémental → mise en cache.
     *
     * Le lock (Cache::lock) empêche deux requêtes concurrentes de régénérer
     * le résumé simultanément pour le même utilisateur.
     */
    public function getFreshSummary(User $user): TransactionSummary
    {
        $cacheKey = $this->cacheKey($user);

        $cached = Cache::get($cacheKey);
        if ($cached instanceof TransactionSummary) {
            return $cached;
        }

        $summary = $this->getLatestSummary($user);

        // Cas 1 : pas encore de résumé (nouvel utilisateur ou première demande)
        if ($summary === null) {
            $summary = $this->generateBaseline($user);
            Cache::put($cacheKey, $summary, self::CACHE_TTL);
            return $summary;
        }

        // Cas 2 : résumé à jour, aucune action requise
        $categorieIds = $user->categories()->pluck('id');
        if (!$this->hasNewTransactions($summary, $categorieIds)) {
            Cache::put($cacheKey, $summary, self::CACHE_TTL);
            return $summary;
        }

        // Cas 3 : delta détecté → lock par utilisateur avant la mise à jour
        $lock = Cache::lock("summary.generating.{$user->id}", self::LOCK_TIMEOUT);

        try {
            // block() attend jusqu'à LOCK_WAIT secondes si un autre process tient le lock
            $lock->block(self::LOCK_WAIT);

            // Re-lecture après le lock : un autre process a pu appliquer l'incrémental pendant l'attente
            $summary = $this->getLatestSummary($user) ?? $summary;

            if (!$this->hasNewTransactions($summary, $categorieIds)) {
                Cache::put($cacheKey, $summary, self::CACHE_TTL);
                return $summary;
            }

            $summary = $this->applyIncremental($user, $summary, $categorieIds);
            Cache::put($cacheKey, $summary, self::CACHE_TTL);

            return $summary;
        } finally {
            // Le lock est toujours libéré, même en cas d'exception
            $lock->release();
        }
    }

    /**
     * Régénère le résumé complet depuis toutes les transactions de l'utilisateur.
     *
     * Remet la dérive à zéro (la baseline est la source de vérité).
     * Traite les transactions par chunks pour supporter les historiques volumineux.
     * Appelé par GenerateBaselineSummaryJob ; peut aussi être appelé directement
     * pour la première demande d'un nouvel utilisateur.
     */
    public function generateBaseline(User $user): TransactionSummary
    {
        $categorieIds = $user->categories()->pluck('id');
        $aggregats    = $this->aggregator->emptyAggregats();
        $firstTxDate  = null;
        $lastTx       = null;

        Transaction::whereIn('categorie_id', $categorieIds)
            ->with('categorie:id,libelle')
            ->orderBy('date')
            ->orderBy('id')
            ->chunk(self::CHUNK_SIZE, function (Collection $chunk) use (&$aggregats, &$firstTxDate, &$lastTx): void {
                if ($firstTxDate === null) {
                    $firstTxDate = $chunk->first()->date;
                }
                $chunkAggregats = $this->aggregator->compute($chunk);
                $aggregats      = $this->aggregator->merge($aggregats, $chunkAggregats);
                $lastTx         = $chunk->last();
            });

        $now = Carbon::now();

        $summary = TransactionSummary::create([
            'user_id'               => $user->id,
            'type'                  => 'baseline',
            'period_start'          => $firstTxDate ?? $now->toDateString(),
            'period_end'            => $lastTx?->date ?? $now->toDateString(),
            'last_transaction_date' => $lastTx?->date,
            'last_transaction_id'   => $lastTx?->id,
            'aggregats'             => $aggregats,
            'narratif'              => null,
            'generated_at'          => $now,
        ]);

        Cache::forget($this->cacheKey($user));

        return $summary;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // PRIVÉ
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Calcule le delta (transactions après le curseur), fusionne avec la base,
     * et persiste un nouveau résumé de type 'incremental'.
     *
     * @param  Collection<int, string> $categorieIds IDs des catégories de l'utilisateur
     */
    private function applyIncremental(User $user, TransactionSummary $base, Collection $categorieIds): TransactionSummary
    {
        $deltaAggregats = $this->aggregator->emptyAggregats();
        $lastTx         = null;

        $this->deltaQuery($categorieIds, $base)
            ->chunk(self::CHUNK_SIZE, function (Collection $chunk) use (&$deltaAggregats, &$lastTx): void {
                $chunkAggregats = $this->aggregator->compute($chunk);
                $deltaAggregats = $this->aggregator->merge($deltaAggregats, $chunkAggregats);
                $lastTx         = $chunk->last();
            });

        // Race condition résolue par le lock : plus de delta à traiter
        if ($lastTx === null) {
            return $base;
        }

        $mergedAggregats = $this->aggregator->merge($base->aggregats, $deltaAggregats);
        $now             = Carbon::now();

        $summary = TransactionSummary::create([
            'user_id'               => $user->id,
            'type'                  => 'incremental',
            'period_start'          => $base->period_start->toDateString(),
            'period_end'            => $lastTx->date,
            'last_transaction_date' => $lastTx->date,
            'last_transaction_id'   => $lastTx->id,
            'aggregats'             => $mergedAggregats,
            'narratif'              => null,
            'generated_at'          => $now,
        ]);

        Cache::forget($this->cacheKey($user));

        return $summary;
    }

    /**
     * Retourne true s'il existe au moins une transaction postérieure au curseur du résumé.
     *
     * @param  Collection<int, string> $categorieIds
     */
    private function hasNewTransactions(TransactionSummary $summary, Collection $categorieIds): bool
    {
        if ($categorieIds->isEmpty()) {
            return false;
        }

        // Résumé vide (baseline d'un utilisateur sans transaction historique) :
        // vérifie s'il y a maintenant des transactions
        if ($summary->last_transaction_date === null) {
            return Transaction::whereIn('categorie_id', $categorieIds)->exists();
        }

        return $this->deltaQuery($categorieIds, $summary)->exists();
    }

    /**
     * Construit la requête de delta : transactions postérieures au curseur (date + id).
     *
     * Le curseur composite (date, id) évite les collisions sur des transactions
     * enregistrées à la même date. L'ordre (date ASC, id ASC) correspond à l'index
     * composite ajouté sur la table transactions.
     *
     * Si le curseur est null (résumé sans transaction), retourne toutes les transactions.
     *
     * @param  Collection<int, string> $categorieIds
     */
    private function deltaQuery(Collection $categorieIds, TransactionSummary $summary): Builder
    {
        $query = Transaction::whereIn('categorie_id', $categorieIds)
            ->with('categorie:id,libelle')
            ->orderBy('date')
            ->orderBy('id');

        if ($summary->last_transaction_date === null) {
            return $query; // curseur absent → tout est dans le delta
        }

        $lastDate = $summary->last_transaction_date->toDateString();
        $lastId   = $summary->last_transaction_id;

        return $query->where(function (Builder $q) use ($lastDate, $lastId): void {
            $q->where('date', '>', $lastDate)
              ->orWhere(function (Builder $inner) use ($lastDate, $lastId): void {
                  // Même date mais id postérieur (transactions à la même date)
                  $inner->where('date', '=', $lastDate)
                        ->where('id', '>', $lastId);
              });
        });
    }

    private function getLatestSummary(User $user): ?TransactionSummary
    {
        return TransactionSummary::where('user_id', $user->id)
            ->orderByDesc('generated_at')
            ->first();
    }

    private function cacheKey(User $user): string
    {
        return "summary.latest.{$user->id}";
    }
}
