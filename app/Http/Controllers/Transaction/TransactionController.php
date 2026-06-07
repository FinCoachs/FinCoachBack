<?php

namespace App\Http\Controllers\Transaction;

use App\DTOs\Transaction\TransactionDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\TransactionRequest;
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

    public function index()
    {
        try {
            $transactions = $this->transactionService->getByUser();

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
}
