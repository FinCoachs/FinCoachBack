<?php

declare(strict_types=1);

use App\Services\Summary\SummaryAggregator;

// ─── Helper ──────────────────────────────────────────────────────────────────

/**
 * Crée un objet transaction minimal compatible avec SummaryAggregator::compute().
 * Pas d'Eloquent, pas de BD : objet pur pour tester sans infrastructure.
 */
function makeTx(
    float $montant,
    string $type,
    string $categorieId = 'cat-a',
    string $categorieLibelle = 'Alimentation'
): object {
    return (object) [
        'montant'      => $montant,
        'type'         => $type,
        'categorie_id' => $categorieId,
        'categorie'    => (object) ['libelle' => $categorieLibelle],
    ];
}

// ─── compute() ───────────────────────────────────────────────────────────────

describe('SummaryAggregator::compute()', function () {

    it('retourne des agrégats vides pour une liste de transactions vide', function () {
        $aggregator = new SummaryAggregator();
        $result     = $aggregator->compute([]);

        expect($result['total_revenus'])->toBe(0.0)
            ->and($result['total_depenses'])->toBe(0.0)
            ->and($result['net'])->toBe(0.0)
            ->and($result['transaction_count'])->toBe(0)
            ->and($result['revenus_count'])->toBe(0)
            ->and($result['depenses_count'])->toBe(0)
            ->and($result['par_categorie'])->toBe([]);
    });

    it('calcule correctement les totaux sur des transactions mixtes', function () {
        $aggregator   = new SummaryAggregator();
        $transactions = [
            makeTx(400_000, 'revenu',  'cat-salaire', 'Salaire'),
            makeTx(50_000,  'depense', 'cat-food',    'Alimentation'),
            makeTx(30_000,  'depense', 'cat-food',    'Alimentation'),
        ];

        $result = $aggregator->compute($transactions);

        expect($result['total_revenus'])->toBe(400_000.0)
            ->and($result['total_depenses'])->toBe(80_000.0)
            ->and($result['net'])->toBe(320_000.0)
            ->and($result['transaction_count'])->toBe(3)
            ->and($result['revenus_count'])->toBe(1)
            ->and($result['depenses_count'])->toBe(2);
    });

    it('agrège correctement par catégorie', function () {
        $aggregator   = new SummaryAggregator();
        $transactions = [
            makeTx(50_000, 'depense', 'cat-food', 'Alimentation'),
            makeTx(30_000, 'depense', 'cat-food', 'Alimentation'),
            makeTx(20_000, 'depense', 'cat-transport', 'Transport'),
        ];

        $result = $aggregator->compute($transactions);
        $cats   = $result['par_categorie'];

        expect($cats)->toHaveKey('cat-food')
            ->and($cats['cat-food']['total'])->toBe(80_000.0)
            ->and($cats['cat-food']['count'])->toBe(2)
            ->and($cats['cat-food']['libelle'])->toBe('Alimentation')
            ->and($cats)->toHaveKey('cat-transport')
            ->and($cats['cat-transport']['total'])->toBe(20_000.0);
    });

    it('utilise "Non catégorisé" si la categorie est null', function () {
        $aggregator = new SummaryAggregator();
        $tx         = (object) [
            'montant'      => 10_000,
            'type'         => 'depense',
            'categorie_id' => 'cat-unknown',
            'categorie'    => null,
        ];

        $result = $aggregator->compute([$tx]);

        expect($result['par_categorie']['cat-unknown']['libelle'])->toBe('Non catégorisé');
    });

    it('calcule un net négatif quand les dépenses dépassent les revenus', function () {
        $aggregator   = new SummaryAggregator();
        $transactions = [
            makeTx(100_000, 'revenu',  'cat-s', 'Salaire'),
            makeTx(150_000, 'depense', 'cat-f', 'Alimentation'),
        ];

        $result = $aggregator->compute($transactions);

        expect($result['net'])->toBe(-50_000.0);
    });
});

// ─── merge() ─────────────────────────────────────────────────────────────────

describe('SummaryAggregator::merge()', function () {

    it("fusionne deux jeux d'agrégats et donne des totaux exacts", function () {
        $aggregator = new SummaryAggregator();

        $base = $aggregator->compute([
            makeTx(400_000, 'revenu',  'cat-s', 'Salaire'),
            makeTx(50_000,  'depense', 'cat-f', 'Alimentation'),
        ]);

        $delta = $aggregator->compute([
            makeTx(30_000, 'depense', 'cat-f',   'Alimentation'),
            makeTx(20_000, 'depense', 'cat-t',   'Transport'),
        ]);

        $merged = $aggregator->merge($base, $delta);

        expect($merged['total_revenus'])->toBe(400_000.0)
            ->and($merged['total_depenses'])->toBe(100_000.0)   // 50k + 30k + 20k
            ->and($merged['net'])->toBe(300_000.0)
            ->and($merged['transaction_count'])->toBe(4)
            ->and($merged['revenus_count'])->toBe(1)
            ->and($merged['depenses_count'])->toBe(3);
    });

    it("additionne le total d'une catégorie existante sans la dupliquer", function () {
        $aggregator = new SummaryAggregator();

        $base  = $aggregator->compute([makeTx(50_000, 'depense', 'cat-f', 'Alimentation')]);
        $delta = $aggregator->compute([makeTx(30_000, 'depense', 'cat-f', 'Alimentation')]);

        $merged = $aggregator->merge($base, $delta);
        $cats   = $merged['par_categorie'];

        expect($cats)->toHaveCount(1)
            ->and($cats['cat-f']['total'])->toBe(80_000.0)
            ->and($cats['cat-f']['count'])->toBe(2);
    });

    it('insère une nouvelle catégorie du delta absente de la base', function () {
        $aggregator = new SummaryAggregator();

        $base  = $aggregator->compute([makeTx(50_000, 'depense', 'cat-f', 'Alimentation')]);
        $delta = $aggregator->compute([makeTx(20_000, 'depense', 'cat-t', 'Transport')]);

        $merged = $aggregator->merge($base, $delta);

        expect($merged['par_categorie'])->toHaveCount(2)
            ->and($merged['par_categorie'])->toHaveKeys(['cat-f', 'cat-t']);
    });

    it('la fusion de base + delta vide retourne les mêmes agrégats que la base', function () {
        $aggregator = new SummaryAggregator();

        $base  = $aggregator->compute([makeTx(400_000, 'revenu', 'cat-s', 'Salaire')]);
        $delta = $aggregator->emptyAggregats();

        $merged = $aggregator->merge($base, $delta);

        expect($merged['total_revenus'])->toBe($base['total_revenus'])
            ->and($merged['transaction_count'])->toBe($base['transaction_count'])
            ->and($merged['net'])->toBe($base['net']);
    });

    it('le net fusionné est toujours égal à total_revenus - total_depenses', function () {
        $aggregator = new SummaryAggregator();

        $base  = $aggregator->compute([
            makeTx(500_000, 'revenu',  'cat-s', 'Salaire'),
            makeTx(200_000, 'depense', 'cat-f', 'Alimentation'),
        ]);
        $delta = $aggregator->compute([
            makeTx(150_000, 'depense', 'cat-t', 'Transport'),
        ]);

        $merged = $aggregator->merge($base, $delta);

        expect($merged['net'])
            ->toBe($merged['total_revenus'] - $merged['total_depenses']);
    });
});

// ─── emptyAggregats() ────────────────────────────────────────────────────────

describe('SummaryAggregator::emptyAggregats()', function () {

    it('retourne une structure complète avec toutes les clés à zéro', function () {
        $aggregator = new SummaryAggregator();
        $empty      = $aggregator->emptyAggregats();

        expect($empty)->toHaveKeys([
            'total_revenus', 'total_depenses', 'net',
            'transaction_count', 'revenus_count', 'depenses_count',
            'par_categorie',
        ])
            ->and($empty['transaction_count'])->toBe(0)
            ->and($empty['par_categorie'])->toBe([]);
    });
});
