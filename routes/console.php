<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;

// Nettoyer automatiquement les tokens Sanctum expirés depuis plus de 24 heures, chaque jour
Schedule::command('sanctum:prune-expired --hours=24')->daily();

// Générer les rapports mensuels le 1er de chaque mois à 06h00
Schedule::command('rapports:mensuel')->monthlyOn(1, '06:00');
