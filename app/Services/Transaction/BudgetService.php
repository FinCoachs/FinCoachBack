<?php

namespace App\Services\Transaction;

use App\DTOs\Transaction\CreateBudgetDTO;
use App\Models\Categorie;
use App\Models\Transaction;
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
        // la categorie appartient déjà à l'utilisateur → pas besoin de filtrer par user_id
        return (float) $categorie->transactions()
            ->where('type', 'depense')
            ->sum('montant');
    }
}
