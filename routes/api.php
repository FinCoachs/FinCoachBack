<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Resources\UserResource;
use App\Http\Controllers\Authentication\AuthController;
use App\Http\Controllers\Authentication\GoogleController;
use App\Http\Controllers\AlerteController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\RecommandationsController;
use App\Http\Controllers\Transaction\BudgetController;
use App\Http\Controllers\Transaction\TransactionController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Compte\CompteController;

Route::post('/auth/google', GoogleController::class);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::post('/scheduler/run', function (Request $request) {
    if ($request->header('X-Scheduler-Token') !== env('SCHEDULER_TOKEN')) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }
    \Illuminate\Support\Facades\Artisan::call('schedule:run');
    return response()->json(['success' => true]);
});

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user', function (Request $request) {
        return response()->json([
            'success' => true,
            'data'    => new UserResource($request->user()),
        ]);
    });

    Route::patch('/user',          [AuthController::class, 'updateInfo']);
    Route::patch('/user/password', [AuthController::class, 'updatePassword']);
    Route::patch('/user/profil',   [AuthController::class, 'updateProfil']);

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
        Route::put('/{id}',        [BudgetController::class, 'update']);
        Route::delete('/{id}',     [BudgetController::class, 'destroy']);
    });

    // Transactions
    Route::prefix('transactions')->group(function () {
        Route::post('/',       [TransactionController::class, 'store']);
        Route::get('/',        [TransactionController::class, 'index']);
        Route::get('/filter',  [TransactionController::class, 'filterTransaction']);
        Route::get('/stats',   [TransactionController::class, 'montantStatistique']);
        Route::delete('/{id}', [TransactionController::class, 'destroy']);
    });

    // Comptes
    Route::prefix('comptes')->group(function () {
        Route::post('/',       [CompteController::class, 'store']);
        Route::get('/',        [CompteController::class, 'index']);
        Route::delete('/{id}', [CompteController::class, 'destroy']);
    });

    // Alertes & push token
    Route::get('/alertes',           [AlerteController::class, 'index']);
    Route::post('/alertes/lire',     [AlerteController::class, 'markAllRead']);
    Route::post('/user/push-token',  [AlerteController::class, 'savePushToken']);

    // Recommandations IA
    Route::get('/recommandations', [RecommandationsController::class, 'index']);

    // Chat Coach IA (ancienne API simple)
    Route::get('/messages',    [MessageController::class, 'index']);
    Route::post('/messages',   [MessageController::class, 'store']);
    Route::delete('/messages', [MessageController::class, 'destroy']);

    // Chat IA avec streaming SSE (30 messages/min)
    Route::prefix('chat')->middleware('throttle:30,1')->group(function () {
        Route::post('/messages',                                    [ChatController::class, 'send']);
        Route::get('/conversation/latest',                          [ChatController::class, 'latestConversation']);
        Route::get('/conversations/{conversationId}/messages',      [ChatController::class, 'messages']);
        Route::delete('/conversations/{conversationId}',            [ChatController::class, 'deleteConversation']);
    });

});
