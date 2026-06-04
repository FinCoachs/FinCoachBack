<?php

namespace App\Services\Transaction;

use App\DTOs\Transaction\CreateBudgetDTO;
use App\Models\Categorie;
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

}
