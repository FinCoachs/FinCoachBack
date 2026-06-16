<?php

declare(strict_types=1);

namespace App\Services;

use App\Ai\Agents\FinCoachAgent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\ConversationStore;
use Laravel\Ai\Responses\StreamableAgentResponse;

/**
 * Orchestre les échanges avec le FinCoachAgent.
 *
 * - stream()               : démarre ou continue une conversation et retourne une réponse SSE
 * - latestConversationId() : retourne l'ID de la dernière conversation de l'utilisateur
 * - getMessages()          : retourne les messages d'une conversation (vérification de propriété)
 * - deleteConversation()   : supprime une conversation et ses messages (vérification de propriété)
 */
final class ChatService
{
    public function __construct(
        private readonly FinCoachAgent $agent,
        private readonly ConversationStore $conversationStore,
    ) {}

    public function stream(User $user, string $message, ?string $conversationId = null): StreamableAgentResponse
    {
        if ($conversationId !== null) {
            $this->agent->continue($conversationId, as: $user);
        } else {
            $this->agent->forUser($user);
        }

        return $this->agent->stream($message);
    }

    public function latestConversationId(User $user): ?string
    {
        return $this->conversationStore->latestConversationId($user->id);
    }

    /**
     * Retourne les messages d'une conversation en vérifiant qu'elle appartient à l'utilisateur.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getMessages(User $user, string $conversationId, int $limit = 50): array
    {
        if (!$this->conversationBelongsToUser($conversationId, $user)) {
            return [];
        }

        $messages = $this->conversationStore->getLatestConversationMessages($conversationId, $limit);

        return $messages->map(fn ($msg) => [
            'role'    => $msg->role,
            'content' => $msg->content,
        ])->values()->all();
    }

    /**
     * Supprime une conversation et tous ses messages (vérification de propriété stricte).
     */
    public function deleteConversation(User $user, string $conversationId): bool
    {
        if (!$this->conversationBelongsToUser($conversationId, $user)) {
            return false;
        }

        $messagesTable      = config('ai.conversations.tables.messages', 'agent_conversation_messages');
        $conversationsTable = config('ai.conversations.tables.conversations', 'agent_conversations');

        DB::table($messagesTable)->where('conversation_id', $conversationId)->delete();
        DB::table($conversationsTable)->where('id', $conversationId)->delete();

        return true;
    }

    private function conversationBelongsToUser(string $conversationId, User $user): bool
    {
        $conversationsTable = config('ai.conversations.tables.conversations', 'agent_conversations');

        return DB::table($conversationsTable)
            ->where('id', $conversationId)
            ->where('user_id', $user->id)
            ->exists();
    }
}
