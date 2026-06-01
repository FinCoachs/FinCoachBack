<?php

namespace App\Services\Authentication;

use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthService
{
    public function handleGoogleCallback(Request $request)
    {
        $request->validate([
            'token' => 'string|required'
        ]);

        try {
            // Vérification du token Google par Socialite
            $googleUser = Socialite::driver('google')
                ->stateless()
                ->userFromToken($request->token);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Token Google invalide.',
            ], 401);
        }

        // Création ou mise à jour de l'utilisateur
        $user = User::updateOrCreate(
            ['email' => $googleUser->getEmail()],
            [
                'name'              => $googleUser->getName(),
                'google_id'         => $googleUser->getId(),
                'avatar'            => $googleUser->getAvatar(),
                'email_verified_at' => now()
            ]
        );

        // Générer le token Sanctum
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'data'    => [
                'user'  => $user,
                'token' => $token,
            ],
        ]);
    }
}
