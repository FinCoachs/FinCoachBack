<?php
    namespace App\Response\Auth;

    use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

    class RegisterResponse implements RegisterResponseContract{

        public function toResponse($request){
            return response()->json([
                'success' => true,
                'message' => "Utilisateur enregistré avec succès"
            ], 201);
        }

    }
