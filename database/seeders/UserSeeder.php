<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Utilisateur de test avec credentials connus
        User::factory()->create([
            'name'  => 'Light',
            'email' => 'lightss@gmail.om',
        ]);

        // Utilisateurs supplémentaires aléatoires
        User::factory(4)->create();
    }
}
