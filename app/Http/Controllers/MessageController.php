<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    /** GET /messages — historique de la conversation */
    public function index()
    {
        $messages = Message::query()
            ->where('user_id', '=', Auth::id())
            ->orderBy('date', 'asc')
            ->get(['id', 'contenu', 'expediteur', 'date']);

        return response()->json(['success' => true, 'data' => $messages]);
    }

    /** POST /messages — enregistre le message utilisateur (réponse IA à implémenter) */
    public function store(Request $request)
    {
        $request->validate(['contenu' => 'required|string|max:2000']);

        $user = Auth::user();

        $message = Message::create([
            'user_id'    => $user->id,
            'contenu'    => $request->contenu,
            'expediteur' => 'utilisateur',
            'date'       => now(),
        ]);

        return response()->json([
            'success' => true,
            'data'    => $message,
        ]);
    }

    /** DELETE /messages — efface l'historique de conversation */
    public function destroy()
    {
        Message::where('user_id', Auth::id())->delete();

        return response()->json(['success' => true, 'message' => 'Historique effacé.']);
    }
}
