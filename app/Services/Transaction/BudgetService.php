<?php

namespace App\Services\Transaction;

use App\DTOs\Transaction\CreateBudgetDTO;
use App\Models\Categorie;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class BudgetService
{
    /**
     * Créer un budget (catégorie) pour l'utilisateur connecté.
     *
     * @throws \Exception
     */
    public function createBudget(CreateBudgetDTO $dto): Categorie
    {
        return Categorie::create([
            'libelle' => $dto->libelle,
            'plafond' => $dto->plafond,
            'user_id' => Auth::id(),
        ]);
    }

    public function getCategories(): Collection
    {
        return Categorie::where('user_id', Auth::id())
            ->withSum('depenses', 'montant')
            ->get();
    }

    /**
     * Calcule le pourcentage du plafond d'un budget déjà consommé.
     * Retourne 0 si le plafond est invalide pour éviter une division par zéro.
     */
    public function budgetPourcentage(float $prixTotal, float $plafond): float
    {
        if ($plafond <= 0) return 0.0;

        return ($prixTotal * 100) / $plafond;
    }

    /**
     * Calcule le total des dépenses d'une catégorie (budget) pour l'utilisateur connecté.
     * Utilise la relation Eloquent pour éviter de charger toutes les transactions en mémoire.
     */
    public function prixTotal(Categorie $categorie): float
    {
        return (float) $categorie->transactions()
            ->where('type', 'depense')
            ->sum('montant');
    }
}
