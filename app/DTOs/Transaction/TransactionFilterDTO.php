<?php

namespace App\DTOs\Transaction;

use App\Http\Requests\TransactionFilterRequest;

readonly class TransactionFilterDTO
{
    public function __construct(
        public ?string $categorie_id,
        public ?string $type,
        public ?string $search,
    ) {}

    public static function fromRequest(TransactionFilterRequest $request): self
    {
        return new self(
            $request->validated('categorie_id'),
            $request->validated('type'),
            $request->validated('search'),
        );
    }
}
