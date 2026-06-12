<?php

namespace App\Http\Controllers\Transaction;

use App\DTOs\Transaction\TransactionDTO;
use App\DTOs\Transaction\TransactionFilterDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\TransactionFilterRequest;
use App\Http\Requests\TransactionRequest;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Http\Resources\Transaction\TransactionResource;
use App\Services\NotificationService;
use App\Services\Transaction\BudgetService;
use App\Services\Transaction\TransactionService;
use Exception;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    public function __construct(
        private readonly TransactionService  $transactionService,
        private readonly BudgetService       $budgetService,
        private readonly NotificationService $notificationService
    ) {}

    public function store(TransactionRequest $request)
    {
        try {
            $transaction = $this->transactionService->createTransaction(
                TransactionDTO::fromRequest($request)
            );

            // ── Vérification seuil budgétaire ────────────────────────────────
            if ($transaction->type === 'depense' && $transaction->categorie_id) {
                $this->checkBudgetThreshold($transaction->categorie_id);
            }

            return response()->json([
                'success' => true,
                'message' => 'Transaction créée avec succès',
                'data'    => new TransactionResource($transaction),
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    //Fonction pour la liste des transactions d'un utilisateur
    public function index(Request $request)
    {
        try {
            $limit = $request->integer('limit', 6);
            $transactions = $this->transactionService->getByUser($limit);

            return response()->json([
                'success' => true,
                'data'    => TransactionResource::collection($transactions),
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    //Fonction pour filtrer les transactions
    public function filterTransaction(TransactionFilterRequest $request)
    {
        try {
            $transactions = $this->transactionService->filter(
                TransactionFilterDTO::fromRequest($request)
            );

            return response()->json([
                'success' => true,
                'data'    => TransactionResource::collection($transactions),
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(string $id): \Illuminate\Http\JsonResponse
    {
        try {
            $transaction = Transaction::whereHas(
                'categorie', fn($q) => $q->where('user_id', Auth::id())
            )->findOrFail($id);

            $transaction->delete();

            return response()->json(['success' => true, 'message' => 'Transaction supprimée']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /** Vérifie si une catégorie a franchi un seuil budgétaire et envoie une alerte. */
    private function checkBudgetThreshold(string $categorieId): void
    {
        try {
            $categorie = \App\Models\Categorie::find($categorieId);
            if (! $categorie || ! $categorie->plafond || $categorie->plafond <= 0) return;

            $total = $this->budgetService->prixTotal($categorie);
            $pct   = $this->budgetService->budgetPourcentage($total, $categorie->plafond);
            $user  = Auth::user();

            // Évite les alertes en double (une seule par seuil franchi)
            $dejaAlerte80  = \App\Models\Alerte::where('user_id', $user->id)
                ->where('message', 'like', "%{$categorie->libelle}%")
                ->where('message', 'like', '%80%')
                ->whereDate('created_at', today())
                ->exists();

            $dejaAlerte100 = \App\Models\Alerte::where('user_id', $user->id)
                ->where('message', 'like', "%{$categorie->libelle}%")
                ->where('message', 'like', '%100%')
                ->whereDate('created_at', today())
                ->exists();

            $restant = number_format($categorie->plafond - $total, 0, ',', ' ');
            $depense = number_format($total, 0, ',', ' ');
            $plafond = number_format($categorie->plafond, 0, ',', ' ');

            if ($pct >= 100 && ! $dejaAlerte100) {
                $this->notificationService->notify(
                    $user,
                    "Budget « {$categorie->libelle} » dépassé ! Vous avez dépensé {$depense} FCFA sur {$plafond} FCFA.",
                    'alerte',
                    '⛔ Budget dépassé'
                );
            } elseif ($pct >= 80 && ! $dejaAlerte80) {
                $this->notificationService->notify(
                    $user,
                    "Budget « {$categorie->libelle} » à " . round($pct) . "%. Il reste {$restant} FCFA.",
                    'alerte',
                    '⚠️ Budget bientôt épuisé'
                );
            }
        } catch (\Throwable) {
            // Ne pas bloquer la transaction si la vérification échoue
        }
    }

    //Fonction pour retourner une sorte de statistique des transactions du mois
    public function montantStatistique(){
        try{
            $revenus = $this->transactionService->sommeTransaction('revenu');
            $depenses = $this->transactionService->sommeTransaction('depense');
            $net = $revenus - $depenses;

            return response()->json([
                'success' => true,
                'data' => [
                    'revenu' => $revenus,
                    'depenses' => $depenses,
                    'net' => $net
                ]
            ]);
        }catch(Exception $e){
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
