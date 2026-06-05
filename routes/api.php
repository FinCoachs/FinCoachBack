<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Resources\UserResource;
use App\Http\Controllers\Authentication\GoogleController;
use App\Http\Controllers\Transaction\BudgetController;
use App\Http\Controllers\Transaction\TranasctionController;

// Route pour la connexion via Google
Route::post('/auth/google', GoogleController::class);

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

    // Routes pour la gestion et configuration du budget
    Route::prefix('budgets')->group(function () {
        Route::post('/create', [BudgetController::class, 'store']);
        Route::get('/categories', [BudgetController::class, 'categories']);
    });

    //Routes pour les transaction
    Route::prefix('transaction')->group(function() {
        Route::post('/create',[TranasctionController::class, 'store']);
        Route::get('/',[TranasctionController::class, 'getTransaction']);
    });

});
