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
        Schema::table('transactions', function (Blueprint $table) {
            // Index composite pour borner la requête delta :
            // WHERE categorie_id IN (...) AND (date > ? OR (date = ? AND id > ?))
            // categorie_id est la jointure user → transaction (via hasManyThrough)
            if (!$this->indexExists('transactions', 'transactions_categorie_id_date_id_index')) {
                $table->index(['categorie_id', 'date', 'id'], 'transactions_categorie_id_date_id_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('transactions_categorie_id_date_id_index');
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        return collect(Schema::getIndexes($table))
            ->contains(fn($i) => $i['name'] === $index);
    }
};
