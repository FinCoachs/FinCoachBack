<?php

namespace App\Services\Compte;

use App\DTOs\Comptes\CreateCompteDTO;
use App\Models\Compte;
use Illuminate\Support\Facades\Auth;

class CompteService{

    //Fonction pour la creation du compte pour l'utilisateur
    public function create(CreateCompteDTO $dto): Compte{
        return Compte::create([
            'user_id' => Auth::id(),
            'libelle' => $dto->libelle,
            'numero' => $dto->numero,
            'date' => now()
        ]);
    }
}
