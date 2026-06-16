<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        // Utilise Schema builder plutôt que du SQL brut pour rester compatible SQLite (tests)
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'expo_push_token')) {
                $table->string('expo_push_token')->nullable();
            }
        });

        Schema::table('alertes', function (Blueprint $table) {
            if (!Schema::hasColumn('alertes', 'type')) {
                $table->string('type')->default('alerte');
            }
            if (!Schema::hasColumn('alertes', 'lue')) {
                $table->boolean('lue')->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'expo_push_token')) {
                $table->dropColumn('expo_push_token');
            }
        });

        Schema::table('alertes', function (Blueprint $table) {
            foreach (['type', 'lue'] as $col) {
                if (Schema::hasColumn('alertes', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
