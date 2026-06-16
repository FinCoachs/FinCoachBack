<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        Schema::create('transaction_summaries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();

            // baseline = régénération complète du mois ; incremental = fusion delta
            $table->enum('type', ['baseline', 'incremental']);

            // Période couverte par ce résumé
            $table->date('period_start');
            $table->date('period_end');

            // Curseur : couple (date + id) pour borner la requête delta sans collision
            $table->date('last_transaction_date')->nullable();
            $table->uuid('last_transaction_id')->nullable();

            // Agrégats calculés de façon déterministe (totaux, répartition par catégorie)
            $table->json('aggregats');

            // Narratif optionnel généré par le LLM ; null = agrégats seuls suffisent
            $table->text('narratif')->nullable();

            // Horodatage du dernier calcul (distinct de updated_at qui change aussi en BDD)
            $table->timestamp('generated_at');

            $table->timestamps();

            // Retrouver rapidement le résumé le plus récent d'un utilisateur
            $table->index(['user_id', 'generated_at']);
            // Filtrer par type (ex. dernière baseline)
            $table->index(['user_id', 'type', 'generated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_summaries');
    }
};
