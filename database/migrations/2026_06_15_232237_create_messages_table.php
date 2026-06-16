<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Neon pgBouncer workaround — see create_conversations_table migration.
    public $withinTransaction = false;

    public function up(): void
    {
        // If the table doesn't exist, create it with the full new schema.
        DB::statement(<<<'SQL'
            CREATE TABLE IF NOT EXISTS messages (
                id              UUID         NOT NULL PRIMARY KEY,
                conversation_id UUID         NULL,
                user_id         UUID         NULL,
                contenu         TEXT         NULL,
                expediteur      VARCHAR(255) NULL,
                role            VARCHAR(255) NULL
                    CHECK (role IN ('user', 'agent', 'system')),
                date            TIMESTAMP(0) WITHOUT TIME ZONE NULL,
                created_at      TIMESTAMP(0) WITHOUT TIME ZONE NULL,
                updated_at      TIMESTAMP(0) WITHOUT TIME ZONE NULL
            )
        SQL);

        // If the table already existed (old schema), add the columns needed by the
        // new chat architecture without removing anything the old MessageController uses.
        DB::statement(<<<'SQL'
            DO $$ BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM information_schema.columns
                    WHERE table_schema = 'public'
                      AND table_name   = 'messages'
                      AND column_name  = 'conversation_id'
                ) THEN
                    ALTER TABLE messages ADD COLUMN conversation_id UUID NULL;
                END IF;

                IF NOT EXISTS (
                    SELECT 1 FROM information_schema.columns
                    WHERE table_schema = 'public'
                      AND table_name   = 'messages'
                      AND column_name  = 'role'
                ) THEN
                    ALTER TABLE messages ADD COLUMN role VARCHAR(255) NULL
                        CHECK (role IN ('user', 'agent', 'system'));
                END IF;

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
