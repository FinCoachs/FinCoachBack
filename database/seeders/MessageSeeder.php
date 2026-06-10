<?php

namespace Database\Seeders;

use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Seeder;

class MessageSeeder extends Seeder
{
    public function run(): void
    {
        User::all()->each(function (User $user) {
            // Alternance agent / utilisateur pour simuler une vraie conversation
            Message::factory(3)->deAgent()->create(['user_id' => $user->id]);
            Message::factory(2)->deUtilisateur()->create(['user_id' => $user->id]);
        });
    }
}
