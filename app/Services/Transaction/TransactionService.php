<?php

namespace App\Services\Transaction;

use App\DTOs\Transaction\TransactionDTO;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;

class TransactionService
{
    public function CreateTransaction(TransactionDTO $dto):Transaction{
        return Transaction::create([
            'user_id' => Auth::id(),
            'categorie_id' => $dto->categorie_id,
            'montant' => $dto->montant,
            'type' => $dto->type,
            'description' => $dto->description,
            'date' => $dto->date
        ]);
    }

    public function getByUser(){
        $transactions = Transaction::all()->where('user_id', Auth::id())->categorie();
        return $transactions;
    }
}
