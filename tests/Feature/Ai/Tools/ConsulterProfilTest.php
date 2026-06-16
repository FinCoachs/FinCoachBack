<?php

declare(strict_types=1);

use App\Ai\Tools\ConsulterProfil;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Tools\Request;

uses(RefreshDatabase::class);

describe('ConsulterProfil', function () {

    it('retourne le nom, le profil et le budget de l\'utilisateur', function () {
        $user = User::factory()->create([
            'name'   => 'Kouamé Yao',
            'profil' => 'Économiser pour un projet immobilier',
            'budget' => 150_000,
        ]);

        $this->actingAs($user);

        $result = (new ConsulterProfil)->handle(new Request([]));

        expect($result)
            ->toContain('Kouamé Yao')
            ->toContain('Économiser pour un projet immobilier')
            ->toContain('150 000');
    });

    it('fonctionne sans profil ni budget (champs nullables)', function () {
        $user = User::factory()->create([
            'name'   => 'Test User',
            'profil' => null,
            'budget' => null,
        ]);

        $this->actingAs($user);

        $result = (new ConsulterProfil)->handle(new Request([]));

        expect($result)->toContain('Test User');
    });

    // ── Test adversarial : ne jamais exposer les données d'un autre user ──

    it("retourne uniquement le profil de l'utilisateur connecté, pas celui d'un autre", function () {
        $userA = User::factory()->create(['name' => 'Alice Secret', 'budget' => 999_999]);
        $userB = User::factory()->create(['name' => 'Bob Normal',   'budget' => 10_000]);

        $this->actingAs($userB);

        $result = (new ConsulterProfil)->handle(new Request([]));

        expect($result)
            ->toContain('Bob Normal')
            ->not->toContain('Alice Secret')
            ->not->toContain('999 999');
    });
});
