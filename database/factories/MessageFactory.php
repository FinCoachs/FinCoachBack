<?php

namespace Database\Factories;

use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'    => User::factory(),
            'contenu'    => fake()->sentence(rand(8, 20)),
            'expediteur' => fake()->randomElement(['agent', 'utilisateur']),
            'date'       => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d H:i:s'),
        ];
    }

    public function deAgent(): static
    {
        return $this->state(fn() => ['expediteur' => 'agent']);
    }

    public function deUtilisateur(): static
    {
        return $this->state(fn() => ['expediteur' => 'utilisateur']);
    }
}
