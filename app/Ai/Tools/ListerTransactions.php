<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

/**
 * Liste les transactions récentes de l'utilisateur connecté avec filtres optionnels.
 * Sécurité : scopé sur auth()->user()->transactions() (via categorie_id), jamais Transaction::where() global.
 */
final class ListerTransactions implements Tool
{
    public function description(): string
    {
        return "Liste les transactions récentes de l'utilisateur avec filtres optionnels par type ou catégorie.";
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'limite' => $schema->integer()
                ->description('Nombre maximum de transactions à retourner (défaut : 10, max : 50).'),
            'type' => $schema->string()
                ->description('Filtrer par type : "revenu" ou "depense".'),
            'categorie' => $schema->string()
                ->description('Filtrer par libellé de catégorie (recherche partielle, insensible à la casse).'),
        ];
    }

    public function handle(Request $request): string
    {
        $user   = auth()->user();
        $limite = min((int) ($request['limite'] ?? 10), 50);

        $query = $user->transactions()
            ->with('categorie:id,libelle', 'compte:id,libelle')
            ->orderByDesc('date')
            ->orderByDesc('id');

        if (filled($request['type'] ?? null)) {
            $query->where('type', $request['type']);
        }

        if (filled($request['categorie'] ?? null)) {
            $query->whereHas('categorie', fn ($q) => $q->where('libelle', 'like', '%' . $request['categorie'] . '%'));
        }

        $transactions = $query->limit($limite)->get();

        if ($transactions->isEmpty()) {
            return "Aucune transaction trouvée avec ces critères.";
        }

        $lignes = $transactions->map(function ($tx): string {
            $signe   = $tx->type === 'revenu' ? '+' : '-';
            $montant = number_format((float) $tx->montant, 0, ',', ' ');
            $desc    = $tx->description ? " — {$tx->description}" : '';
            return "  [{$tx->date}] {$signe}{$montant} FCFA · {$tx->categorie->libelle}{$desc}";
        })->implode("\n");

        $nb = $transactions->count();

        return "{$nb} transaction(s) :\n{$lignes}";
    }
}
