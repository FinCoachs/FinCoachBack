<?php

namespace App\DTOs\Transaction;

use App\Http\Requests\TransactionRequest;

readonly class TransactionDTO
{
    public function __construct(
        public float $montant,
        public string $date,
        public ?string $description,
        public string $type,
        public string $categorie_id,
    ) {}

    public static function fromRequest(TransactionRequest $request){
        return new self(
            $request->validated('montant'),
            $request->validated('date'),
            $request->validated('description'),
            $request->validated('type'),
            $request->validated('categorie_id'),
        );
    }
}
