<?php

declare(strict_types=1);

use App\Ai\Tools\ConsulterSolde;
use App\Models\Compte;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Tools\Request;

uses(RefreshDatabase::class);

// ─── Helpers ─────────────────────────────────────────────────────────────────

function makeUserWithComptes(array $comptes): User
{
    $user = User::factory()->create();
    foreach ($comptes as $data) {
        Compte::factory()->for($user)->create($data);
    }
    return $user;
}

// ─── Tests ───────────────────────────────────────────────────────────────────

describe('ConsulterSolde', function () {

    it('retourne le solde total et le détail des comptes', function () {
        $user = makeUserWithComptes([
            ['libelle' => 'CCP', 'solde' => 200_000, 'numero' => '001'],
            ['libelle' => 'Épargne', 'solde' => 50_000, 'numero' => '002'],
        ]);

        $this->actingAs($user);

        $result = (new ConsulterSolde)->handle(new Request([]));

        expect($result)
            ->toContain('250 000')
            ->toContain('CCP')
            ->toContain('Épargne');
    });

    it("retourne un message vide quand l'utilisateur n'a pas de compte", function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        $result = (new ConsulterSolde)->handle(new Request([]));

        expect($result)->toContain('Aucun compte');
    });

    // ── Test adversarial : isolation cross-compte ──────────────────────────

    it('ne retourne que les comptes de auth()->user() (pas ceux des autres)', function () {
        $userA = makeUserWithComptes([['libelle' => 'Compte A', 'solde' => 999_999, 'numero' => 'A1']]);
        $userB = makeUserWithComptes([['libelle' => 'Compte B', 'solde' => 111_111, 'numero' => 'B1']]);

        // On est authentifié en tant que userB
        $this->actingAs($userB);

        $result = (new ConsulterSolde)->handle(new Request([]));

        expect($result)
            ->toContain('Compte B')
            ->not->toContain('Compte A')
            ->not->toContain('999 999');
    });
});
