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
        Schema::create('type_comptes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('compte_id')->constrained()->cascadeOnDelete();
            $table->string('libelle');
            $table->date('date');
            $table->enum('type', ['banque', 'momo']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('type_comptes');
    }
};
