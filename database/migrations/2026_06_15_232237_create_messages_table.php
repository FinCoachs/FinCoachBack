<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Same Neon pgBouncer workaround as create_conversations_table.
    public bool $withinTransaction = false;

    public function up(): void
    {
        DB::statement(<<<'SQL'
            CREATE TABLE IF NOT EXISTS messages (
                id              UUID         NOT NULL PRIMARY KEY,
                conversation_id UUID         NOT NULL,
                contenu         TEXT         NOT NULL,
                role            VARCHAR(255) NOT NULL
                    CHECK (role IN ('user', 'agent', 'system')),
                created_at      TIMESTAMP(0) WITHOUT TIME ZONE NULL,
                updated_at      TIMESTAMP(0) WITHOUT TIME ZONE NULL
            )
        SQL);

        DB::statement(<<<'SQL'
            DO $$ BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM pg_constraint
                    WHERE conname = 'messages_conversation_id_foreign'
                ) THEN
                    ALTER TABLE messages
                        ADD CONSTRAINT messages_conversation_id_foreign
                        FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE;
                END IF;
            END $$
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
