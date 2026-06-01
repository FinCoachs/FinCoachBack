<?php
    namespace App\Response\Auth;

    use App\Http\Resources\UserResource;
    use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

    class LoginResponse implements LoginResponseContract{

        public function toResponse($request){
            $user = $request->user();

            if ($user) {
                // Générer le token de connexion avec Laravel Sanctum
                $token = $user->createToken('auth_token')->plainTextToken;

                return response()->json([
                    'success' => true,
                    'message' => 'Utilisateur connecté avec succès.',
                    'token' => $token,
                    'token_type' => 'Bearer',
                    'user' => new UserResource($user)
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non connecté.',
            ], 401);
        }
    }
