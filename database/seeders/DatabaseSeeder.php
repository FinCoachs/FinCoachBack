<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CompteSeeder::class,
            CategorieSeeder::class,
            TransactionSeeder::class,
            AlerteSeeder::class,
            MessageSeeder::class,
            RapportMensuelSeeder::class,
        ]);
    }
}
