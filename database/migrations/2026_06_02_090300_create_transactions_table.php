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
        Schema::dropIfExists('transactions');

        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('categorie_id')->constrained()->cascadeOnDelete();
            $table->decimal('montant', 15, 2);
            $table->date('date');
            $table->string('description')->nullable();
            $table->enum('type', ['depense', 'revenu']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
