<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Crée (ou corrige) les tables SDK laravel/ai pour la persistance des conversations IA.
 *
 * Si les tables n'existent pas → création avec user_id VARCHAR(36) (compatible UUID).
 * Si elles existent déjà avec user_id BIGINT → conversion du type via ALTER COLUMN.
 *
 * withinTransaction = false : contourne le SQLSTATE[25P02] de Neon pgBouncer
 * (transaction mode renvoie parfois des connexions en état d'erreur). Chaque
 * DO-block s'exécute en auto-commit indépendant sans passer par BEGIN/COMMIT Laravel.
 */
return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        $conv = config('ai.conversations.tables.conversations', 'agent_conversations');
        $msgs = config('ai.conversations.tables.messages', 'agent_conversation_messages');

        // ── agent_conversations ───────────────────────────────────────────────
        DB::statement(str_replace('__CONV__', $conv, <<<'SQL'
            DO $$ BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM information_schema.tables
                    WHERE table_schema = 'public' AND table_name = '__CONV__'
                ) THEN
                    CREATE TABLE __CONV__ (
                        id         VARCHAR(36)  NOT NULL PRIMARY KEY,
                        user_id    VARCHAR(36)  NULL,
                        title      VARCHAR(255) NOT NULL DEFAULT '',
                        created_at TIMESTAMP(0) WITHOUT TIME ZONE NULL,
                        updated_at TIMESTAMP(0) WITHOUT TIME ZONE NULL
                    );
                    CREATE INDEX ON __CONV__ (user_id);
                    CREATE INDEX ON __CONV__ (user_id, updated_at);
                ELSE
                    -- Corrige user_id si encore BIGINT (migration précédente du SDK)
                    IF EXISTS (
                        SELECT 1 FROM information_schema.columns
                        WHERE table_schema = 'public'
                          AND table_name   = '__CONV__'
                          AND column_name  = 'user_id'
                          AND data_type NOT IN ('character varying', 'text', 'character')
                    ) THEN
                        ALTER TABLE __CONV__
                            ALTER COLUMN user_id TYPE VARCHAR(36) USING user_id::VARCHAR;
                    END IF;
                END IF;
            END $$
        SQL));

        // ── agent_conversation_messages ───────────────────────────────────────
        DB::statement(str_replace('__MSGS__', $msgs, <<<'SQL'
            DO $$ BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM information_schema.tables
                    WHERE table_schema = 'public' AND table_name = '__MSGS__'
                ) THEN
                    CREATE TABLE __MSGS__ (
                        id              VARCHAR(36)  NOT NULL PRIMARY KEY,
                        conversation_id VARCHAR(36)  NOT NULL,
                        user_id         VARCHAR(36)  NULL,
                        agent           VARCHAR(255) NOT NULL DEFAULT '',
                        role            VARCHAR(25)  NOT NULL DEFAULT '',
                        content         TEXT         NOT NULL DEFAULT '',
                        attachments     TEXT         NOT NULL DEFAULT '',
                        tool_calls      TEXT         NOT NULL DEFAULT '',
                        tool_results    TEXT         NOT NULL DEFAULT '',
                        usage           TEXT         NOT NULL DEFAULT '',
                        meta            TEXT         NOT NULL DEFAULT '',
                        created_at      TIMESTAMP(0) WITHOUT TIME ZONE NULL,
                        updated_at      TIMESTAMP(0) WITHOUT TIME ZONE NULL
                    );
                    CREATE INDEX ON __MSGS__ (conversation_id);
                    CREATE INDEX acm_conversation_index ON __MSGS__ (conversation_id, user_id, updated_at);
                    CREATE INDEX acm_user_index ON __MSGS__ (user_id);
                ELSE
                    IF EXISTS (
                        SELECT 1 FROM information_schema.columns
                        WHERE table_schema = 'public'
                          AND table_name   = '__MSGS__'
                          AND column_name  = 'user_id'
                          AND data_type NOT IN ('character varying', 'text', 'character')
                    ) THEN
                        ALTER TABLE __MSGS__
                            ALTER COLUMN user_id TYPE VARCHAR(36) USING user_id::VARCHAR;
                    END IF;
                END IF;
            END $$
        SQL));
    }

    public function down(): void
    {
        Schema::dropIfExists(config('ai.conversations.tables.messages', 'agent_conversation_messages'));
        Schema::dropIfExists(config('ai.conversations.tables.conversations', 'agent_conversations'));
    }
};
