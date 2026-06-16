<?php

declare(strict_types=1);

use App\Models\Categorie;
use App\Models\Compte;
use App\Models\Transaction;
use App\Models\TransactionSummary;
use App\Models\User;
use App\Services\Summary\SummaryAggregator;
use App\Services\Summary\TransactionSummaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

// ─── Helper ──────────────────────────────────────────────────────────────────

/**
 * Crée un utilisateur avec une catégorie et un compte prêts à recevoir des transactions.
 * Retourne [user, categorie, compte].
 */
function makeUser(): array
{
    $user      = User::factory()->create();
    $categorie = Categorie::factory()->for($user)->create(['libelle' => 'Alimentation', 'plafond' => 500]);
    $compte    = Compte::factory()->for($user)->create();
    return [$user, $categorie, $compte];
}

/**
 * Crée une transaction liée à la catégorie et au compte donnés.
 */
function addTx(Categorie $categorie, Compte $compte, float $montant, string $type, string $date): Transaction
{
    return Transaction::factory()->create([
        'categorie_id' => $categorie->id,
        'compte_id'    => $compte->id,
        'montant'      => $montant,
        'type'         => $type,
        'date'         => $date,
    ]);
}

function makeService(): TransactionSummaryService
{
    return new TransactionSummaryService(new SummaryAggregator());
}

// ─── generateBaseline() ──────────────────────────────────────────────────────

describe('generateBaseline()', function () {

    it('crée un résumé de type baseline avec les agrégats exacts', function () {
        [$user, $cat, $compte] = makeUser();
        addTx($cat, $compte, 400_000, 'revenu',  '2026-06-01');
        addTx($cat, $compte, 50_000,  'depense', '2026-06-10');
        addTx($cat, $compte, 30_000,  'depense', '2026-06-15');

        $service = makeService();
        $summary = $service->generateBaseline($user);

        expect($summary->type)->toBe('baseline')
            ->and($summary->aggregats['total_revenus'])->toBe(400_000.0)
            ->and($summary->aggregats['total_depenses'])->toBe(80_000.0)
            ->and($summary->aggregats['net'])->toBe(320_000.0)
            ->and($summary->aggregats['transaction_count'])->toBe(3);
    });

    it('persiste le résumé en base de données', function () {
        [$user, $cat, $compte] = makeUser();
        addTx($cat, $compte, 100_000, 'revenu', '2026-06-01');

        makeService()->generateBaseline($user);

        expect(TransactionSummary::where('user_id', $user->id)->count())->toBe(1);
    });

    it('stocke le curseur sur la dernière transaction traitée', function () {
        [$user, $cat, $compte] = makeUser();
        addTx($cat, $compte, 100_000, 'revenu',  '2026-06-01');
        $last = addTx($cat, $compte, 50_000,  'depense', '2026-06-20');

        $summary = makeService()->generateBaseline($user);

        expect($summary->last_transaction_id)->toBe($last->id)
            ->and($summary->last_transaction_date->toDateString())->toBe('2026-06-20');
    });

    it('produit un résumé vide cohérent pour un utilisateur sans transaction', function () {
        [$user] = makeUser();

        $summary = makeService()->generateBaseline($user);

        expect($summary->aggregats['transaction_count'])->toBe(0)
            ->and($summary->aggregats['net'])->toBe(0.0)
            ->and($summary->last_transaction_id)->toBeNull();
    });

    it('invalide le cache après génération', function () {
        [$user, $cat, $compte] = makeUser();
        addTx($cat, $compte, 100_000, 'revenu', '2026-06-01');

        $service  = makeService();
        $cacheKey = "summary.latest.{$user->id}";
        Cache::put($cacheKey, 'stale', 300);

        $service->generateBaseline($user);

        expect(Cache::has($cacheKey))->toBeFalse();
    });
});

// ─── getFreshSummary() — baseline à la volée ─────────────────────────────────

describe('getFreshSummary() — nouvel utilisateur', function () {

    it("génère une baseline à la volée quand aucun résumé n'existe", function () {
        [$user, $cat, $compte] = makeUser();
        addTx($cat, $compte, 200_000, 'revenu', '2026-06-01');

        $summary = makeService()->getFreshSummary($user);

        expect($summary->type)->toBe('baseline')
            ->and($summary->aggregats['total_revenus'])->toBe(200_000.0);
    });
});

// ─── getFreshSummary() — no-op ───────────────────────────────────────────────

describe('getFreshSummary() — aucune nouvelle transaction (no-op)', function () {

    it('retourne le résumé existant sans créer de nouvel enregistrement', function () {
        [$user, $cat, $compte] = makeUser();
        addTx($cat, $compte, 100_000, 'revenu', '2026-06-01');

        $service  = makeService();
        $baseline = $service->generateBaseline($user);

        // Pas de nouvelle transaction → no-op
        $fresh = $service->getFreshSummary($user);

        expect(TransactionSummary::where('user_id', $user->id)->count())->toBe(1)
            ->and($fresh->id)->toBe($baseline->id);
    });

    it('retourne depuis le cache si disponible', function () {
        [$user, $cat, $compte] = makeUser();
        addTx($cat, $compte, 100_000, 'revenu', '2026-06-01');

        $service  = makeService();
        $baseline = $service->generateBaseline($user);

        // Premier appel : met en cache
        $first  = $service->getFreshSummary($user);
        // Deuxième appel : doit venir du cache (même objet)
        $second = $service->getFreshSummary($user);

        expect($first->id)->toBe($second->id);
    });
});

// ─── getFreshSummary() — incrémental ─────────────────────────────────────────

describe('getFreshSummary() — mise à jour incrémentale', function () {

    it('applique le delta et retourne des agrégats fusionnés exacts', function () {
        [$user, $cat, $compte] = makeUser();
        addTx($cat, $compte, 400_000, 'revenu',  '2026-06-01');
        addTx($cat, $compte, 50_000,  'depense', '2026-06-10');

        $service = makeService();
        $service->generateBaseline($user);

        // Nouvelle transaction après la baseline
        addTx($cat, $compte, 30_000, 'depense', '2026-06-20');

        $fresh = $service->getFreshSummary($user);

        expect($fresh->type)->toBe('incremental')
            ->and($fresh->aggregats['total_depenses'])->toBe(80_000.0)  // 50k + 30k
            ->and($fresh->aggregats['net'])->toBe(320_000.0)
            ->and($fresh->aggregats['transaction_count'])->toBe(3);
    });

    it('avance le curseur sur la dernière transaction du delta', function () {
        [$user, $cat, $compte] = makeUser();
        addTx($cat, $compte, 100_000, 'revenu', '2026-06-01');

        $service = makeService();
        $service->generateBaseline($user);

        $newTx = addTx($cat, $compte, 20_000, 'depense', '2026-06-25');
        $fresh  = $service->getFreshSummary($user);

        expect($fresh->last_transaction_id)->toBe($newTx->id);
    });

    it('crée exactement un résumé incremental en base', function () {
        [$user, $cat, $compte] = makeUser();
        addTx($cat, $compte, 100_000, 'revenu', '2026-06-01');

        $service = makeService();
        $service->generateBaseline($user);
        addTx($cat, $compte, 20_000, 'depense', '2026-06-20');
        $service->getFreshSummary($user);

        expect(TransactionSummary::where('user_id', $user->id)->count())->toBe(2)
            ->and(TransactionSummary::where('user_id', $user->id)->where('type', 'incremental')->count())->toBe(1);
    });

    it('le net incrémental est toujours cohérent avec les totaux', function () {
        [$user, $cat, $compte] = makeUser();
        addTx($cat, $compte, 500_000, 'revenu',  '2026-06-01');
        addTx($cat, $compte, 200_000, 'depense', '2026-06-10');

        $service = makeService();
        $service->generateBaseline($user);
        addTx($cat, $compte, 150_000, 'depense', '2026-06-20');

        $fresh = $service->getFreshSummary($user);
        $a     = $fresh->aggregats;

        expect($a['net'])->toBe($a['total_revenus'] - $a['total_depenses']);
    });
});

// ─── Concurrence (lock) ──────────────────────────────────────────────────────

describe('getFreshSummary() — protection contre les doubles générations', function () {

    it("ne crée qu'un seul résumé incrémental même avec deux appels simultanés", function () {
        [$user, $cat, $compte] = makeUser();
        addTx($cat, $compte, 100_000, 'revenu', '2026-06-01');

        $service = makeService();
        $service->generateBaseline($user);
        addTx($cat, $compte, 20_000, 'depense', '2026-06-20');

        // Simule deux appels concurrents : le second obtient le lock après le premier
        $service->getFreshSummary($user);
        Cache::forget("summary.latest.{$user->id}"); // force re-lecture BD pour le 2e appel
        $service->getFreshSummary($user);

        // Le second appel doit détecter qu'il n'y a plus de delta et ne pas créer de doublon
        expect(TransactionSummary::where('user_id', $user->id)->where('type', 'incremental')->count())->toBe(1);
    });
});

// ─── Utilisateur sans catégorie ───────────────────────────────────────────────

describe('cas limites', function () {

    it("retourne un résumé vide pour un utilisateur sans catégorie ni transaction", function () {
        $user    = User::factory()->create();
        $summary = makeService()->getFreshSummary($user);

        expect($summary->aggregats['transaction_count'])->toBe(0)
            ->and($summary->isEmpty())->toBeTrue();
    });
});
