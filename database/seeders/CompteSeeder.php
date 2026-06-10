<?php

namespace Database\Seeders;

use App\Models\Compte;
use App\Models\User;
use Illuminate\Database\Seeder;

class CompteSeeder extends Seeder
{
    public function run(): void
    {
        User::all()->each(function (User $user) {
            Compte::factory(2)->create(['user_id' => $user->id]);
        });
    }
}
