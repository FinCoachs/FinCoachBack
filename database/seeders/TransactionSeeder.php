<?php

namespace Database\Seeders;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    public function run(): void
    {
        // Charge comptes et categories en une seule requête par utilisateur
        User::with(['comptes', 'categories'])->each(function (User $user) {
            if ($user->comptes->isEmpty() || $user->categories->isEmpty()) {
                return;
            }

            $user->categories->each(function ($categorie) use ($user) {
                // 4 à 8 transactions aléatoires par catégorie
                Transaction::factory(rand(4, 8))->create([
                    'categorie_id' => $categorie->id,
                    'compte_id'    => $user->comptes->random()->id,
                ]);
            });
        });
    }
}
