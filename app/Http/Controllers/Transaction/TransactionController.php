<?php

namespace App\Http\Controllers\Transaction;

use App\DTOs\Transaction\TransactionDTO;
use App\DTOs\Transaction\TransactionFilterDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\TransactionFilterRequest;
use App\Http\Requests\TransactionRequest;
use Illuminate\Http\Request;
use App\Http\Resources\Transaction\TransactionResource;
use App\Services\Transaction\TransactionService;
use Exception;

class TransactionController extends Controller
{
    public function __construct(
        private readonly TransactionService $transactionService
    ) {}

    public function store(TransactionRequest $request)
    {
        try {
            $transaction = $this->transactionService->createTransaction(
                TransactionDTO::fromRequest($request)
            );

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
