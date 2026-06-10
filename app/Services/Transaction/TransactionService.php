<?php

namespace App\Services\Transaction;

use App\DTOs\Transaction\TransactionDTO;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class TransactionService
{
    public function createTransaction(TransactionDTO $dto): Transaction
    {
        return Transaction::create([
            'categorie_id' => $dto->categorie_id,
            'compte_id'    => $dto->compte_id,
            'montant'      => $dto->montant,
            'type'         => $dto->type,
            'description'  => $dto->description,
            'date'         => $dto->date,
        ]);
    }

    public function getByUser(int $limit = 6): Collection
    {
        return Transaction::with(['categorie', 'compte'])
            ->whereHas('categorie', fn($q) => $q->where('user_id', Auth::id()))
            ->latest('date')
            ->limit($limit)
            ->get();
    }

    public function sommeTransaction(string $type): float
    {
        return (float) Transaction::whereHas('categorie', fn($q) => $q->where('user_id', Auth::id()))
            ->where('type', $type)
            ->sum('montant');
    }
}
