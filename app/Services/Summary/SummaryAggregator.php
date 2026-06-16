<?php

declare(strict_types=1);

namespace App\Services\Summary;

/**
 * Calcul DÉTERMINISTE des agrégats de transactions.
 *
 * Classe PURE : aucune I/O (pas de DB, pas de fichier, pas de HTTP).
 * Reçoit des données, renvoie des données. 100 % testable en isolation.
 *
 * Format des agrégats :
 * [
 *   'total_revenus'     => float,
 *   'total_depenses'    => float,
 *   'net'               => float,   // total_revenus - total_depenses
 *   'transaction_count' => int,
 *   'revenus_count'     => int,
 *   'depenses_count'    => int,
 *   'par_categorie'     => [
 *       '<categorie_id>' => [
 *           'libelle' => string,
 *           'total'   => float,
 *           'count'   => int,
 *           'type'    => 'revenu'|'depense',
 *       ],
 *       ...
 *   ],
 * ]
 */
final class SummaryAggregator
{
    /**
     * Calcule les agrégats à partir d'un jeu de transactions.
     *
     * Chaque transaction doit exposer les propriétés suivantes :
     *   - montant      (float|numeric-string) — toujours positif
     *   - type         ('revenu'|'depense')
     *   - categorie_id (string)
     *   - categorie    (object avec ->libelle, ou null)
     *
     * @param  iterable<object> $transactions
     * @return array<string, mixed>
     */
    public function compute(iterable $transactions): array
    {
        $aggregats = $this->emptyAggregats();

        foreach ($transactions as $tx) {
            $montant     = (float) $tx->montant;
            $categorieId = (string) $tx->categorie_id;
            $libelle     = (string) ($tx->categorie?->libelle ?? 'Non catégorisé');

            if ($tx->type === 'revenu') {
                $aggregats['total_revenus'] += $montant;
                $aggregats['revenus_count']++;
            } else {
                $aggregats['total_depenses'] += $montant;
                $aggregats['depenses_count']++;
            }

            $aggregats['transaction_count']++;

            if (!isset($aggregats['par_categorie'][$categorieId])) {
                $aggregats['par_categorie'][$categorieId] = [
                    'libelle' => $libelle,
                    'total'   => 0.0,
                    'count'   => 0,
                    'type'    => $tx->type,
                ];
            }

            $aggregats['par_categorie'][$categorieId]['total'] += $montant;
            $aggregats['par_categorie'][$categorieId]['count']++;
        }

        // net recalculé après la boucle pour éviter l'accumulation d'erreurs flottantes
        $aggregats['net'] = $aggregats['total_revenus'] - $aggregats['total_depenses'];

        return $aggregats;
    }

    /**
     * Fusionne deux jeux d'agrégats par simple addition déterministe.
     *
     * Aucun appel LLM, aucune dérive possible. La baseline remet la dérive à zéro
     * une fois par mois ; l'incrémental ne fait que sommer.
     *
     * @param  array<string, mixed> $base  Agrégats du résumé existant (jusqu'au curseur)
     * @param  array<string, mixed> $delta Agrégats calculés sur les nouvelles transactions
     * @return array<string, mixed>        Agrégats fusionnés
     */
    public function merge(array $base, array $delta): array
    {
        $merged = $base;

        $merged['total_revenus']    += $delta['total_revenus'];
        $merged['total_depenses']   += $delta['total_depenses'];
        $merged['transaction_count'] += $delta['transaction_count'];
        $merged['revenus_count']    += $delta['revenus_count'];
        $merged['depenses_count']   += $delta['depenses_count'];

        // net recalculé depuis les totaux fusionnés pour rester exact
        $merged['net'] = $merged['total_revenus'] - $merged['total_depenses'];

        // Fusion par catégorie : addition si déjà présente, insertion sinon
        foreach ($delta['par_categorie'] as $categorieId => $catDelta) {
            if (isset($merged['par_categorie'][$categorieId])) {
                $merged['par_categorie'][$categorieId]['total'] += $catDelta['total'];
                $merged['par_categorie'][$categorieId]['count'] += $catDelta['count'];
                // libelle et type sont stables : pas de mise à jour nécessaire
            } else {
                $merged['par_categorie'][$categorieId] = $catDelta;
            }
        }

        return $merged;
    }

    /**
     * Retourne un jeu d'agrégats vide et cohérent.
     * Utilisé pour initialiser le calcul et comme résumé d'un utilisateur sans transaction.
     *
     * @return array<string, mixed>
     */
    public function emptyAggregats(): array
    {
        return [
            'total_revenus'     => 0.0,
            'total_depenses'    => 0.0,
            'net'               => 0.0,
            'transaction_count' => 0,
            'revenus_count'     => 0,
            'depenses_count'    => 0,
            'par_categorie'     => [],
        ];
    }
}
