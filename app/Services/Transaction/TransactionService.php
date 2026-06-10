<?php

namespace App\Services\Transaction;

use App\DTOs\Transaction\TransactionDTO;
use App\DTOs\Transaction\TransactionFilterDTO;
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

    public function filter(TransactionFilterDTO $dto): Collection
    {
        return Transaction::with(['categorie', 'compte'])
            ->whereHas('categorie', fn($q) => $q->where('user_id', Auth::id()))
            ->when($dto->categorie_id, fn($q) => $q->where('categorie_id', $dto->categorie_id))
            ->when($dto->type,         fn($q) => $q->where('type', $dto->type))
            ->when($dto->search,       fn($q) => $q->where('description', 'like', "%{$dto->search}%"))
            ->latest('date')
            ->get();
    }

    public function sommeTransaction(string $type): float
    {
        return (float) Transaction::whereHas('categorie', fn($q) => $q->where('user_id', Auth::id()))
            ->where('type', $type)
            ->sum('montant');
    }
}
