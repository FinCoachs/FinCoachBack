<?php

namespace Database\Factories;

use App\Models\Compte;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Compte>
 */
class CompteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'libelle' => fake()->randomElement([
                'Compte Courant',
                'Compte Épargne',
                'Compte Pro',
                'Livret A',
                'Compte Titre',
            ]),
            'numero' => fake()->unique()->numerify('##########'),
            'solde'  => 0.00,
            'date'   => fake()->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
        ];
    }
}
