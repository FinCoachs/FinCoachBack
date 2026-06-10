<?php

namespace App\Policies\Transaction;

use App\Models\Categorie;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class BudgetPolicies
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Cette fonction vérifie si le plafond du budget de la categorie de l'utilisateur a déjà fait les 1 mois
     */
    public function updatePlafond(User $user, Categorie $categorie): Response
    {
        return ($categorie->user_id === $user->id && $categorie->created_at->diffInDays(now()) >= 31)
            ? Response::allow()
            : Response::deny('Vous ne pouvez mettre à jour le plafond qu\'après 31 jours.');
    }
}
