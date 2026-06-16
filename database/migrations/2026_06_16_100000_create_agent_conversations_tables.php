<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Crée (ou corrige) les tables SDK laravel/ai pour la persistance des conversations IA.
 *
 * Si les tables n'existent pas → création avec user_id VARCHAR(36) (compatible UUID).
 * Si elles existent déjà avec user_id BIGINT (migration précédente) → conversion du type.
 */
return new class extends Migration
{
    public function up(): void
    {
        $conversationsTable = config('ai.conversations.tables.conversations', 'agent_conversations');
        $messagesTable      = config('ai.conversations.tables.messages', 'agent_conversation_messages');

        if (!Schema::hasTable($conversationsTable)) {
            Schema::create($conversationsTable, function (Blueprint $table) {
                $table->string('id', 36)->primary();
                $table->string('user_id', 36)->nullable()->index();
                $table->string('title');
                $table->timestamps();

                $table->index(['user_id', 'updated_at']);
            });
        } else {
            // Correction du type si user_id est encore BIGINT
            $type = Schema::getColumnType($conversationsTable, 'user_id');
            if (!str_contains($type, 'char') && !str_contains($type, 'text') && !str_contains($type, 'varchar')) {
                DB::statement("ALTER TABLE {$conversationsTable} ALTER COLUMN user_id TYPE VARCHAR(36) USING user_id::VARCHAR");
            }
        }

        if (!Schema::hasTable($messagesTable)) {
            Schema::create($messagesTable, function (Blueprint $table) {
                $table->string('id', 36)->primary();
                $table->string('conversation_id', 36)->index();
                $table->string('user_id', 36)->nullable();
                $table->string('agent');
                $table->string('role', 25);
                $table->text('content');
                $table->text('attachments');
                $table->text('tool_calls');
                $table->text('tool_results');
                $table->text('usage');
                $table->text('meta');
                $table->timestamps();

                $table->index(['conversation_id', 'user_id', 'updated_at'], 'acm_conversation_index');
                $table->index(['user_id'], 'acm_user_index');
            });
        } else {
            $type = Schema::getColumnType($messagesTable, 'user_id');
            if (!str_contains($type, 'char') && !str_contains($type, 'text') && !str_contains($type, 'varchar')) {
                DB::statement("ALTER TABLE {$messagesTable} ALTER COLUMN user_id TYPE VARCHAR(36) USING user_id::VARCHAR");
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists(config('ai.conversations.tables.messages', 'agent_conversation_messages'));
        Schema::dropIfExists(config('ai.conversations.tables.conversations', 'agent_conversations'));
    }
};
