<?php

declare(strict_types=1);

use App\Ai\Tools\ListerTransactions;
use App\Models\Categorie;
use App\Models\Compte;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Tools\Request;

uses(RefreshDatabase::class);

// ─── Helpers ─────────────────────────────────────────────────────────────────

function userWithTransactions(): array
{
    $user   = User::factory()->create();
    $cat    = Categorie::factory()->for($user)->create(['libelle' => 'Alimentation']);
    $compte = Compte::factory()->for($user)->create();

    $tx1 = Transaction::factory()->create([
        'categorie_id' => $cat->id,
        'compte_id'    => $compte->id,
        'montant'      => 15_000,
        'type'         => 'depense',
        'description'  => 'Supermarché',
        'date'         => '2026-06-01',
    ]);

    $tx2 = Transaction::factory()->create([
        'categorie_id' => $cat->id,
        'compte_id'    => $compte->id,
        'montant'      => 200_000,
        'type'         => 'revenu',
        'description'  => 'Salaire',
        'date'         => '2026-06-15',
    ]);

    return compact('user', 'cat', 'tx1', 'tx2');
}

// ─── Tests ───────────────────────────────────────────────────────────────────

describe('ListerTransactions', function () {

    it('retourne les transactions récentes sans filtre', function () {
        ['user' => $user] = userWithTransactions();
        $this->actingAs($user);

        $result = (new ListerTransactions)->handle(new Request([]));

        expect($result)->toContain('Supermarché')->toContain('Salaire');
    });

    it('filtre par type "depense"', function () {
        ['user' => $user] = userWithTransactions();
        $this->actingAs($user);

        $result = (new ListerTransactions)->handle(new Request(['type' => 'depense']));

        expect($result)
            ->toContain('Supermarché')
            ->not->toContain('Salaire');
    });

    it('filtre par libellé de catégorie (partiel, insensible à la casse)', function () {
        ['user' => $user] = userWithTransactions();
        $this->actingAs($user);

        $result = (new ListerTransactions)->handle(new Request(['categorie' => 'aliment']));

        expect($result)->toContain('Alimentation');
    });

    it('limite le nombre de résultats à 50 maximum même si le LLM demande plus', function () {
        $user   = User::factory()->create();
        $cat    = Categorie::factory()->for($user)->create();
        $compte = Compte::factory()->for($user)->create();

        Transaction::factory()->count(60)->create([
            'categorie_id' => $cat->id,
            'compte_id'    => $compte->id,
        ]);

        $this->actingAs($user);

        $result = (new ListerTransactions)->handle(new Request(['limite' => 999]));

        expect($result)->toContain('50 transaction');
    });

    it("retourne un message approprié quand aucun résultat ne correspond", function () {
        ['user' => $user] = userWithTransactions();
        $this->actingAs($user);

        $result = (new ListerTransactions)->handle(new Request(['type' => 'depense', 'categorie' => 'INEXISTANT']));

        expect($result)->toContain('Aucune transaction');
    });

    // ── Test adversarial : isolation cross-compte ──────────────────────────

    it("ne liste que les transactions de auth()->user(), pas celles d'un autre utilisateur", function () {
        ['user' => $userA] = userWithTransactions();
        ['user' => $userB] = userWithTransactions();

        // Authentifié en tant que userB — doit voir uniquement SES 2 transactions
        $this->actingAs($userB);

        $result = (new ListerTransactions)->handle(new Request([]));

        expect($result)->toContain('2 transaction');
    });
});
