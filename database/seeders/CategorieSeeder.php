<?php

namespace Database\Seeders;

use App\Models\Categorie;
use App\Models\User;
use Illuminate\Database\Seeder;

class CategorieSeeder extends Seeder
{
    // Catégories par défaut créées pour chaque utilisateur
    private static array $categories = [
        ['libelle' => 'Alimentation',  'plafond' => 400],
        ['libelle' => 'Transport',     'plafond' => 200],
        ['libelle' => 'Logement',      'plafond' => 900],
        ['libelle' => 'Loisirs',       'plafond' => 150],
        ['libelle' => 'Salaire',       'plafond' => 3000],
        ['libelle' => 'Santé',         'plafond' => 120],
    ];

    public function run(): void
    {
        User::all()->each(function (User $user) {
            foreach (self::$categories as $cat) {
                Categorie::factory()->create([
                    'user_id' => $user->id,
                    'libelle' => $cat['libelle'],
                    'plafond' => $cat['plafond'],
                ]);
            }
        });
    }
}
