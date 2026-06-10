<?php

namespace Database\Factories;

use App\Models\Categorie;
use App\Models\Compte;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    private static array $descriptionsDepense = [
        'Courses supermarché',
        'Carburant',
        'Restaurant midi',
        'Cinéma',
        'Facture EDF',
        'Loyer',
        'Pharmacie',
        'Shopping vêtements',
        'Abonnement Netflix',
        'Livraison à domicile',
        'Coiffeur',
        'Transport en commun',
        'Réparation voiture',
        'Matériel informatique',
    ];

    private static array $descriptionsRevenu = [
        'Salaire mensuel',
        'Remboursement frais',
        'Virement reçu',
        'Mission freelance',
        'Prime annuelle',
        'Dividendes',
        'Remboursement sécurité sociale',
        'Bonus trimestriel',
    ];

    public function definition(): array
    {
        $type = fake()->randomElement(['depense', 'revenu']);

        return [
            'categorie_id' => Categorie::factory(),
            'compte_id'    => Compte::factory(),
            'montant'      => $type === 'revenu'
                ? fake()->randomFloat(2, 500, 4000)
                : fake()->randomFloat(2, 5, 500),
            'type'         => $type,
            'description'  => fake()->randomElement(
                $type === 'depense' ? self::$descriptionsDepense : self::$descriptionsRevenu
            ),
            'date' => fake()->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
        ];
    }

    public function depense(): static
    {
        return $this->state(fn() => [
            'type'        => 'depense',
            'montant'     => fake()->randomFloat(2, 5, 500),
            'description' => fake()->randomElement(self::$descriptionsDepense),
        ]);
    }

    public function revenu(): static
    {
        return $this->state(fn() => [
            'type'        => 'revenu',
            'montant'     => fake()->randomFloat(2, 500, 4000),
            'description' => fake()->randomElement(self::$descriptionsRevenu),
        ]);
    }
}
