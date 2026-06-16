<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ChatController extends Controller
{
    public function __construct(private readonly ChatService $chatService) {}

    /**
     * Envoie un message au FinCoachAgent et retourne une réponse SSE.
     *
     * POST /chat/messages
     * Body : { message: string, conversation_id?: string }
     */
    public function send(Request $request): Response
    {
        $validated = $request->validate([
            'message'         => 'required|string|max:2000',
            'conversation_id' => 'nullable|string|max:36',
        ]);

        $response = $this->chatService->stream(
            $request->user(),
            $validated['message'],
            $validated['conversation_id'] ?? null,
        );

        return $response->toResponse($request);
    }

    /**
     * Retourne l'ID de la dernière conversation de l'utilisateur.
     *
     * GET /chat/conversation/latest
     */
    public function latestConversation(Request $request): JsonResponse
    {
        $conversationId = $this->chatService->latestConversationId($request->user());

        return response()->json([
            'success'         => true,
            'conversation_id' => $conversationId,
        ]);
    }

    /**
     * Retourne les messages d'une conversation (propriété vérifiée).
     *
     * GET /chat/conversations/{conversationId}/messages
     */
    public function messages(Request $request, string $conversationId): JsonResponse
    {
        $messages = $this->chatService->getMessages(
            $request->user(),
            $conversationId,
            limit: 50,
        );

        return response()->json([
            'success' => true,
            'data'    => $messages,
        ]);
    }

    /**
     * Supprime une conversation et ses messages (propriété vérifiée).
     *
     * DELETE /chat/conversations/{conversationId}
     */
    public function deleteConversation(Request $request, string $conversationId): JsonResponse
    {
        $deleted = $this->chatService->deleteConversation($request->user(), $conversationId);

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'Conversation introuvable ou accès non autorisé.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Conversation supprimée.',
        ]);
    }
}
