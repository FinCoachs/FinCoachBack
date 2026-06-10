<?php

namespace Database\Factories;

use App\Models\Categorie;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Categorie>
 */
class CategorieFactory extends Factory
{
    private static array $catalogue = [
        'Alimentation'  => 400,
        'Transport'     => 200,
        'Logement'      => 900,
        'Loisirs'       => 150,
        'Santé'         => 120,
        'Habillement'   => 200,
        'Salaire'       => 3000,
        'Freelance'     => 1500,
        'Abonnements'   => 80,
        'Restaurants'   => 150,
        'Épargne'       => 500,
        'Éducation'     => 100,
    ];

    public function definition(): array
    {
        $libelle = fake()->randomElement(array_keys(self::$catalogue));

        return [
            'user_id' => User::factory(),
            'libelle' => $libelle,
            'plafond' => self::$catalogue[$libelle],
        ];
    }
}
