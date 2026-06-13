<?php

namespace App\Http\Controllers;

use App\Ai\Agents\FinCoachAgent;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    /** GET /messages — historique de la conversation */
    public function index()
    {
        $messages = Message::where('user_id', Auth::id())
            ->orderBy('date')
            ->get(['id', 'contenu', 'expediteur', 'date']);

        return response()->json(['success' => true, 'data' => $messages]);
    }

    /** POST /messages — envoie un message et retourne la réponse complète du coach */
    public function store(Request $request)
    {
        $request->validate(['contenu' => 'required|string|max:2000']);

        $user = Auth::user();
        $now  = now();

        // Sauvegarde du message utilisateur
        Message::create([
            'user_id'    => $user->id,
            'contenu'    => $request->contenu,
            'expediteur' => 'utilisateur',
            'date'       => $now,
        ]);

        // Génération de la réponse IA
        $response = (new FinCoachAgent($user))->prompt($request->contenu);
        $reponse  = (string) $response;

        // Sauvegarde de la réponse du coach
        $agentMsg = Message::create([
            'user_id'    => $user->id,
            'contenu'    => $reponse,
            'expediteur' => 'agent',
            'date'       => now(),
        ]);

        return response()->json([
            'success' => true,
            'data'    => $agentMsg,
        ]);
    }

    /** DELETE /messages — efface l'historique de conversation */
    public function destroy()
    {
        Message::where('user_id', Auth::id())->delete();

        return response()->json(['success' => true, 'message' => 'Historique effacé.']);
    }
}
