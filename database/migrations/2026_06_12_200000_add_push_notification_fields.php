<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Désactive la transaction globale : chaque instruction est indépendante
    public $withinTransaction = false;

    public function up(): void
    {
        DB::statement('ALTER TABLE users    ADD COLUMN IF NOT EXISTS expo_push_token VARCHAR(255) NULL');
        DB::statement("ALTER TABLE alertes  ADD COLUMN IF NOT EXISTS type VARCHAR(255) NOT NULL DEFAULT 'alerte'");
        DB::statement('ALTER TABLE alertes  ADD COLUMN IF NOT EXISTS lue  BOOLEAN      NOT NULL DEFAULT FALSE');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE users   DROP COLUMN IF EXISTS expo_push_token');
        DB::statement('ALTER TABLE alertes DROP COLUMN IF EXISTS type');
        DB::statement('ALTER TABLE alertes DROP COLUMN IF EXISTS lue');
    }
};
