<?php

namespace App\Http\Controllers\Transaction;

use App\DTOs\Transaction\TransactionDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\TransactionRequest;
use App\Http\Resources\Transaction\TransactionResource;
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

    public function getTransaction(){
        try{
            $transaction = $this->transactionservice->getByUser();
                return response()->json([
                    'success' => true,
                    'data' => new TransactionResource($transaction)
                ], 200);

        }catch(Exception $e){
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
