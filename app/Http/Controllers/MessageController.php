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

    /** POST /messages — envoie un message et streame la réponse du coach */
    public function store(Request $request)
    {
        $request->validate(['contenu' => 'required|string|max:2000']);

        $user = Auth::user();

        // Sauvegarde du message utilisateur
        Message::create([
            'user_id'    => $user->id,
            'contenu'    => $request->contenu,
            'expediteur' => 'utilisateur',
            'date'       => now(),
        ]);

        $agent = new FinCoachAgent($user);

        // Stream la réponse et sauvegarde en DB quand le stream est terminé
        return $agent
            ->stream($request->contenu)
            ->then(function ($response) use ($user) {
                Message::create([
                    'user_id'    => $user->id,
                    'contenu'    => $response->text,
                    'expediteur' => 'agent',
                    'date'       => now(),
                ]);
            });
    }

    /** DELETE /messages — efface l'historique de conversation */
    public function destroy()
    {
        Message::where('user_id', Auth::id())->delete();

        return response()->json(['success' => true, 'message' => 'Historique effacé.']);
    }
}
