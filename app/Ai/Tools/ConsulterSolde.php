<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

/**
 * Retourne le solde de tous les comptes de l'utilisateur connecté.
 * Sécurité : toujours scopé sur auth()->user()->comptes, jamais sur Compte::all().
 */
final class ConsulterSolde implements Tool
{
    public function description(): string
    {
        return "Consulte le solde actuel de chaque compte bancaire de l'utilisateur connecté et calcule le total.";
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function handle(Request $request): string
    {
        $user   = auth()->user();
        $comptes = $user->comptes()->get(['libelle', 'solde', 'numero']);

        if ($comptes->isEmpty()) {
            return "Aucun compte bancaire enregistré.";
        }

        $total = $comptes->sum('solde');
        $lignes = $comptes->map(function ($compte): string {
            $solde = number_format((float) $compte->solde, 0, ',', ' ');
            return "  - {$compte->libelle} (n° {$compte->numero}) : {$solde} FCFA";
        })->implode("\n");

        $totalFormate = number_format((float) $total, 0, ',', ' ');

        return "Solde total : {$totalFormate} FCFA\n\nDétail par compte :\n{$lignes}";
    }
}
