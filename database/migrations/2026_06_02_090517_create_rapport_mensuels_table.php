<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('rapport_mensuels');

        Schema::create('rapport_mensuels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('transaction_id')->constrained()->cascadeOnDelete();
            $table->text('description');
            $table->date('mois');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rapport_mensuels');
    }
};
