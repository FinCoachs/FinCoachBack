<?php

declare(strict_types=1);

use App\Ai\Agents\FinCoachAgent;
use App\Models\Categorie;
use App\Models\Compte;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

// ─── Helpers ─────────────────────────────────────────────────────────────────

function chatUser(): User
{
    return User::factory()->create();
}

function chatUserWithData(): User
{
    $user   = User::factory()->create();
    $cat    = Categorie::factory()->for($user)->create(['libelle' => 'Salaire']);
    $compte = Compte::factory()->for($user)->create(['solde' => 300_000]);
    Transaction::factory()->create([
        'categorie_id' => $cat->id,
        'compte_id'    => $compte->id,
        'montant'      => 300_000,
        'type'         => 'revenu',
        'date'         => now()->toDateString(),
    ]);
    return $user;
}

function fakeConversation(User $user, string $conversationId): void
{
    $table = config('ai.conversations.tables.conversations', 'agent_conversations');
    DB::table($table)->insert([
        'id'         => $conversationId,
        'user_id'    => $user->id,
        'title'      => 'Test conversation',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

// ─── Authentification ─────────────────────────────────────────────────────────

describe('POST /api/chat/messages — authentification', function () {

    it('renvoie 401 si non authentifié', function () {
        $this->postJson('/api/chat/messages', ['message' => 'Bonjour'])
            ->assertStatus(401);
    });
});

// ─── Validation ──────────────────────────────────────────────────────────────

describe('POST /api/chat/messages — validation', function () {

    it('rejette un message vide', function () {
        FinCoachAgent::fake(['Bonjour !']);
        $user = chatUser();

        $this->actingAs($user)
            ->postJson('/api/chat/messages', ['message' => ''])
            ->assertStatus(422);
    });

    it('rejette un message trop long (> 2000 caractères)', function () {
        FinCoachAgent::fake(['Réponse.']);
        $user = chatUser();

        $this->actingAs($user)
            ->postJson('/api/chat/messages', ['message' => str_repeat('x', 2001)])
            ->assertStatus(422);
    });
});

// ─── Flux de chat ─────────────────────────────────────────────────────────────

describe('POST /api/chat/messages — flux normal', function () {

    it("retourne une réponse SSE quand l'agent est fakés", function () {
        FinCoachAgent::fake(['Bonjour ! Je suis FinCoach, comment puis-je vous aider ?']);
        $user = chatUser();

        $response = $this->actingAs($user)
            ->postJson('/api/chat/messages', ['message' => 'Bonjour']);

        // La réponse SSE a le Content-Type text/event-stream
        $response->assertStatus(200);
        expect($response->headers->get('Content-Type'))->toContain('text/event-stream');
    });

    it('accepte un conversation_id existant pour continuer une conversation', function () {
        FinCoachAgent::fake(['Suite de la conversation.']);
        $user           = chatUser();
        $conversationId = (string) \Illuminate\Support\Str::uuid();

        fakeConversation($user, $conversationId);

        $response = $this->actingAs($user)
            ->postJson('/api/chat/messages', [
                'message'         => 'Quelle est ma situation ?',
                'conversation_id' => $conversationId,
            ]);

        $response->assertStatus(200);
        FinCoachAgent::assertPrompted('Quelle est ma situation ?');
    });
});

// ─── GET /chat/conversation/latest ───────────────────────────────────────────

describe('GET /api/chat/conversation/latest', function () {

    it('retourne null quand aucune conversation', function () {
        $user = chatUser();

        $this->actingAs($user)
            ->getJson('/api/chat/conversation/latest')
            ->assertOk()
            ->assertJson(['success' => true, 'conversation_id' => null]);
    });

    it('retourne l\'ID de la dernière conversation', function () {
        $user           = chatUser();
        $conversationId = (string) \Illuminate\Support\Str::uuid();

        fakeConversation($user, $conversationId);

        $this->actingAs($user)
            ->getJson('/api/chat/conversation/latest')
            ->assertOk()
            ->assertJson(['conversation_id' => $conversationId]);
    });
});

// ─── DELETE /chat/conversations/{id} ─────────────────────────────────────────

describe('DELETE /api/chat/conversations/{id}', function () {

    it('supprime une conversation appartenant à l\'utilisateur', function () {
        $user           = chatUser();
        $conversationId = (string) \Illuminate\Support\Str::uuid();

        fakeConversation($user, $conversationId);

        $this->actingAs($user)
            ->deleteJson("/api/chat/conversations/{$conversationId}")
            ->assertOk()
            ->assertJson(['success' => true]);

        $table = config('ai.conversations.tables.conversations', 'agent_conversations');
        expect(DB::table($table)->where('id', $conversationId)->exists())->toBeFalse();
    });

    // ── Test adversarial : cross-account conversation deletion ────────────

    it('refuse de supprimer la conversation d\'un autre utilisateur (403)', function () {
        $userA          = chatUser();
        $userB          = chatUser();
        $conversationId = (string) \Illuminate\Support\Str::uuid();

        // Conversation appartient à userA
        fakeConversation($userA, $conversationId);

        // userB essaie de la supprimer
        $this->actingAs($userB)
            ->deleteJson("/api/chat/conversations/{$conversationId}")
            ->assertStatus(403);

        // La conversation de userA est intacte
        $table = config('ai.conversations.tables.conversations', 'agent_conversations');
        expect(DB::table($table)->where('id', $conversationId)->exists())->toBeTrue();
    });
});

// ─── GET /chat/conversations/{id}/messages ────────────────────────────────────

describe('GET /api/chat/conversations/{id}/messages', function () {

    it("retourne un tableau vide pour une conversation d'un autre utilisateur (pas d'exception)", function () {
        $userA          = chatUser();
        $userB          = chatUser();
        $conversationId = (string) \Illuminate\Support\Str::uuid();

        fakeConversation($userA, $conversationId);

        $this->actingAs($userB)
            ->getJson("/api/chat/conversations/{$conversationId}/messages")
            ->assertOk()
            ->assertJson(['success' => true, 'data' => []]);
    });
});
