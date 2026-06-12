<?php

namespace App\Http\Controllers;

use App\Models\Alerte;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AlerteController extends Controller
{
    /** Liste les alertes de l'utilisateur (non lues en premier). */
    public function index()
    {
        $alertes = Alerte::where('user_id', Auth::id())
            ->orderBy('lue')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $alertes,
            'unread'  => $alertes->where('lue', false)->count(),
        ]);
    }

    /** Marque toutes les alertes comme lues. */
    public function markAllRead()
    {
        Alerte::where('user_id', Auth::id())
            ->where('lue', false)
            ->update(['lue' => true]);

        return response()->json(['success' => true]);
    }

    /** Enregistre le token Expo Push de l'appareil. */
    public function savePushToken(Request $request)
    {
        $request->validate(['token' => 'required|string']);
        Auth::user()->update(['expo_push_token' => $request->token]);
        return response()->json(['success' => true]);
    }
}
