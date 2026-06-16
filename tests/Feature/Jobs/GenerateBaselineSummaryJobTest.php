<?php

declare(strict_types=1);

use App\Jobs\GenerateBaselineSummaryJob;
use App\Models\Categorie;
use App\Models\Compte;
use App\Models\Transaction;
use App\Models\TransactionSummary;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

// ─── Helper ──────────────────────────────────────────────────────────────────

function makeUserWithTransaction(): User
{
    $user  = User::factory()->create();
    $cat   = Categorie::factory()->for($user)->create();
    $compte = Compte::factory()->for($user)->create();
    Transaction::factory()->create([
        'categorie_id' => $cat->id,
        'compte_id'    => $compte->id,
        'montant'      => 100_000,
        'type'         => 'revenu',
        'date'         => now()->toDateString(),
    ]);
    return $user;
}

// ─── Dispatch & exécution ────────────────────────────────────────────────────

describe('GenerateBaselineSummaryJob', function () {

    it('génère une baseline en base après exécution', function () {
        $user = makeUserWithTransaction();

        (new GenerateBaselineSummaryJob($user->id))->handle(
            app(\App\Services\Summary\TransactionSummaryService::class)
        );

        expect(TransactionSummary::where('user_id', $user->id)->where('type', 'baseline')->count())
            ->toBe(1);
    });

    it('est idempotent : ne crée pas de seconde baseline si une existe déjà ce mois', function () {
        $user = makeUserWithTransaction();
        $service = app(\App\Services\Summary\TransactionSummaryService::class);
        $job     = new GenerateBaselineSummaryJob($user->id);

        $job->handle($service); // première exécution
        $job->handle($service); // deuxième exécution — doit être ignorée

        expect(TransactionSummary::where('user_id', $user->id)->where('type', 'baseline')->count())
            ->toBe(1);
    });

    it("ne plante pas si l'utilisateur n'existe plus", function () {
        $job = new GenerateBaselineSummaryJob('uuid-inexistant');

        expect(fn () => $job->handle(app(\App\Services\Summary\TransactionSummaryService::class)))
            ->not->toThrow(\Throwable::class);
    });

    it('fonctionne pour un utilisateur sans transaction (résumé vide)', function () {
        $user = User::factory()->create();

        (new GenerateBaselineSummaryJob($user->id))->handle(
            app(\App\Services\Summary\TransactionSummaryService::class)
        );

        $summary = TransactionSummary::where('user_id', $user->id)->first();

        expect($summary)->not->toBeNull()
            ->and($summary->aggregats['transaction_count'])->toBe(0);
    });

    it('est bien dispatchable sur la queue', function () {
        Queue::fake();

        GenerateBaselineSummaryJob::dispatch('some-user-id');

        Queue::assertPushed(GenerateBaselineSummaryJob::class, fn ($job) => true);
    });

    it('a 3 tentatives et un backoff de 60 secondes configurés', function () {
        $job = new GenerateBaselineSummaryJob('some-id');

        expect($job->tries)->toBe(3)
            ->and($job->backoff)->toBe(60);
    });
});
