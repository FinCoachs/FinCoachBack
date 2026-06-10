<?php

namespace Database\Factories;

use App\Models\RapportMensuel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RapportMensuel>
 */
class RapportMensuelFactory extends Factory
{
    public function definition(): array
    {
        $mois = fake()->dateTimeBetween('-6 months', 'now');

        return [
            'user_id'     => User::factory(),
            'description' => sprintf(
                'Rapport de %s %s : revenus %.2f€, dépenses %.2f€. %s',
                fake()->monthName(),
                $mois->format('Y'),
                fake()->randomFloat(2, 1500, 5000),
                fake()->randomFloat(2, 500, 2000),
                fake()->sentence()
            ),
            'mois' => $mois->format('Y-m-01'),
        ];
    }
}
