<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

/**
 * Retourne le profil financier de l'utilisateur connecté.
 * Utile quand l'utilisateur n'a pas encore de transactions (onboarding).
 */
final class ConsulterProfil implements Tool
{
    public function description(): string
    {
        return "Consulte le profil financier de l'utilisateur (nom, budget mensuel, objectifs financiers).";
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function handle(Request $request): string
    {
        $user  = auth()->user();
        $infos = ["Nom : {$user->name}"];

        if (filled($user->profil)) {
            $infos[] = "Profil / objectifs : {$user->profil}";
        }

        if (filled($user->budget)) {
            $budget  = number_format((float) $user->budget, 0, ',', ' ');
            $infos[] = "Budget mensuel : {$budget} FCFA";
        }

        return implode("\n", $infos);
    }
}
