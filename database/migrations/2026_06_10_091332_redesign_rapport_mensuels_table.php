<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('rapport_mensuels', function (Blueprint $table) {
            if (Schema::hasColumn('rapport_mensuels', 'transaction_id')) {
                $table->dropForeign(['transaction_id']);
                $table->dropColumn('transaction_id');
            }
            if (!Schema::hasColumn('rapport_mensuels', 'user_id')) {
                $table->foreignUuid('user_id')->after('id')->constrained()->cascadeOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('rapport_mensuels', function (Blueprint $table) {
            if (Schema::hasColumn('rapport_mensuels', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
            if (!Schema::hasColumn('rapport_mensuels', 'transaction_id')) {
                $table->foreignUuid('transaction_id')->after('id')->constrained()->cascadeOnDelete();
            }
        });
    }
};
