<?php

namespace App\Services\Authentication;

use App\Http\Resources\UserResource;
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
            /** @var \Laravel\Socialite\Two\User $googleUser */
            $googleUser = Socialite::driver('google')
                ->stateless()
                ->userFromToken($request->token);
        } catch (\Exception) {
            return response()->json([
                'message' => 'Token Google invalide.',
            ], 401);
        }

        $user = User::updateOrCreate(
            ['email' => $googleUser->getEmail()],
            [
                'name'              => $googleUser->getName(),
                'google_id'         => $googleUser->getId(),
                'avatar'            => $googleUser->getAvatar(),
                'email_verified_at' => now()
            ]
        );

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'data'    => [
                'user'  => new UserResource($user),
                'token' => $token,
            ],
        ]);
    }
}
