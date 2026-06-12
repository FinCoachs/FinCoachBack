<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('expo_push_token')->nullable()->after('profil');
        });

        Schema::table('alertes', function (Blueprint $table) {
            $table->string('type')->default('alerte')->after('message'); // 'alerte' | 'info'
            $table->boolean('lue')->default(false)->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('users',   fn (Blueprint $t) => $t->dropColumn('expo_push_token'));
        Schema::table('alertes', fn (Blueprint $t) => $t->dropColumn(['type', 'lue']));
    }
};
