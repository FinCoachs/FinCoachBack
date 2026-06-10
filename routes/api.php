<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Resources\UserResource;
use App\Http\Controllers\Authentication\GoogleController;
use App\Http\Controllers\Transaction\BudgetController;
use App\Http\Controllers\Transaction\TransactionController;
use App\Http\Controllers\Compte\CompteController;

Route::post('/auth/google', GoogleController::class);

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user', function (Request $request) {
        return new UserResource($request->user());
    });

    Route::post('/logout', function (Request $request) {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'success' => true,
            'message' => 'Utilisateur déconnecté avec succès',
        ], 200);
    });

    // Budgets / catégories
    Route::prefix('budgets')->group(function () {
        Route::post('/',           [BudgetController::class, 'store']);
        Route::get('/categories',  [BudgetController::class, 'categories']);
    });

    // Transactions
    Route::prefix('transactions')->group(function () {
        Route::post('/',       [TransactionController::class, 'store']);
        Route::get('/',        [TransactionController::class, 'index']);
        Route::get('/filter',  [TransactionController::class, 'filterTransaction']);
        Route::get('/stats',   [TransactionController::class, 'montantStatistique']);
    });

    // Comptes
    Route::prefix('comptes')->group(function () {
        Route::post('/', [CompteController::class, 'store']);
        Route::get('/',  [CompteController::class, 'index']);
    });

});
