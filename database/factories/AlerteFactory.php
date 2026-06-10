<?php

namespace Database\Factories;

use App\Models\Alerte;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Alerte>
 */
class AlerteFactory extends Factory
{
    private static array $messages = [
        'Votre plafond "Alimentation" est atteint à 80%.',
        'Solde bas détecté sur votre Compte Courant.',
        'Votre rapport mensuel est disponible.',
        'Une nouvelle transaction a été enregistrée.',
        'Le budget "Loisirs" a été dépassé ce mois-ci.',
        'Rappel : une facture est à régler cette semaine.',
        'Votre épargne mensuelle objectif n\'est pas atteint.',
        'Dépenses inhabituelles détectées sur votre compte.',
    ];

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'message' => fake()->randomElement(self::$messages),
            'date'    => fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
        ];
    }
}
