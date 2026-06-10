<?php

namespace Database\Seeders;

use App\Models\RapportMensuel;
use App\Models\User;
use Illuminate\Database\Seeder;

class RapportMensuelSeeder extends Seeder
{
    public function run(): void
    {
        User::all()->each(fn(User $user) =>
            RapportMensuel::factory(3)->create(['user_id' => $user->id])
        );
    }
}
