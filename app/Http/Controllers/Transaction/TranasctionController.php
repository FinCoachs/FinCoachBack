<?php

namespace App\Http\Controllers\Transaction;

use App\DTOs\Transaction\TransactionDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\TransactionRequest;
use App\Services\Transaction\TransactionService;
use Exception;

class TranasctionController extends Controller
{
    public function __construct(
        private readonly TransactionService $transactionservice
    ){}

    public function store(TransactionRequest $request){
        try{
            $transaction = $this->transactionservice->CreateTransaction(
                TransactionDTO::fromRequest($request)
            );

            return response()->json([
                'success' => true,
                'message' => 'Transaction créé avec succès',
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
