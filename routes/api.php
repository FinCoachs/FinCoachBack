<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Resources\UserResource;
use App\Http\Controllers\Authentication\GoogleController;
use App\Http\Controllers\Transaction\BudgetControlelr;

// Route pour la connexion via Google
Route::post('/auth/google', [GoogleController::class, 'handleGoogleCallback']);

Route::middleware('auth:sanctum')->group(function () {
    // Récupérer les détails de l'utilisateur connecté
    Route::get('/user', function (Request $request) {
        return new UserResource($request->user());
    });

    // Déconnecter l'utilisateur en révoquant (supprimant) son token actuel 
    Route::post('/logout', function (Request $request) {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur déconnecté avec succès'
        ], 200);
    });

    // Route pour la création du budget
    Route::post('/create-budget', [BudgetControlelr::class, 'createBudget']);

});
