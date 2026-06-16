<?php

use App\Jobs\GenerateBaselineSummaryJob;
use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Nettoyer automatiquement les tokens Sanctum expirés depuis plus de 24 heures, chaque jour
Schedule::command('sanctum:prune-expired --hours=24')->daily();

// Générer les rapports mensuels le 1er de chaque mois à 06h00
Schedule::command('rapports:mensuel')->monthlyOn(1, '06:00');

// Baseline mensuelle de résumé de transactions — le 1er du mois à 05h00 (avant le rapport)
// Les utilisateurs sont itérés par chunks de 100 pour ne jamais tout charger en mémoire.
// GenerateBaselineSummaryJob est idempotent : re-dispatcher ne crée pas de doublon.
Schedule::call(function () {
    User::query()
        ->select('id')
        ->chunkById(100, function ($users): void {
            foreach ($users as $user) {
                GenerateBaselineSummaryJob::dispatch($user->id);
            }
        });
})->monthlyOn(1, '05:00')->name('summary:baseline-mensuelle')->withoutOverlapping();
