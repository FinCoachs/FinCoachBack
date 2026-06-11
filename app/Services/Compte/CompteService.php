<?php

namespace App\Services\Compte;

use App\DTOs\Comptes\CreateCompteDTO;
use App\Models\Compte;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class CompteService
{
    public function create(CreateCompteDTO $dto): Compte
    {
        return Compte::create([
            'user_id'       => Auth::id(),
            'libelle'       => $dto->libelle,
            'numero'        => $dto->numero,
            'solde_initial' => $dto->solde_initial,
            'date'          => now(),
        ]);
    }

    public function comptes(): Collection
    {
        return Compte::where('user_id', Auth::id())
            ->withSum('revenus', 'montant')
            ->withSum('depenses', 'montant')
            ->get();
    }

    // Calcule le solde réel d'un compte à partir de ses transactions
    public function solde(Compte $compte): float
    {
        $revenus  = $compte->transactions()->where('type', 'revenu')->sum('montant');
        $depenses = $compte->transactions()->where('type', 'depense')->sum('montant');

        return (float) ($revenus - $depenses);
    }
}
