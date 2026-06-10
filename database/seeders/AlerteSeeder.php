<?php

namespace Database\Seeders;

use App\Models\Alerte;
use App\Models\User;
use Illuminate\Database\Seeder;

class AlerteSeeder extends Seeder
{
    public function run(): void
    {
        User::all()->each(fn(User $user) =>
            Alerte::factory(3)->create(['user_id' => $user->id])
        );
    }
}
