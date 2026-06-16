<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Neon pgBouncer (transaction mode) can return connections with an already-aborted
    // transaction state. Running outside a transaction gives each statement its own
    // clean connection checkout, avoiding SQLSTATE[25P02] on the first DDL.
    public bool $withinTransaction = false;

    public function up(): void
    {
        DB::statement(<<<'SQL'
            CREATE TABLE IF NOT EXISTS conversations (
                id         UUID         NOT NULL PRIMARY KEY,
                user_id    UUID         NOT NULL,
                title      VARCHAR(255) NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NULL
            )
        SQL);

        DB::statement(<<<'SQL'
            DO $$ BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM pg_constraint
                    WHERE conname = 'conversations_user_id_foreign'
                ) THEN
                    ALTER TABLE conversations
                        ADD CONSTRAINT conversations_user_id_foreign
                        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;
                END IF;
            END $$
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
