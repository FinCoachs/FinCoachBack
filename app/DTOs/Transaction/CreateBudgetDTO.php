<?php

namespace App\DTOs\Transaction;

use App\Http\Requests\BudgetRequest;

readonly class CreateBudgetDTO
{
    public function __construct(
        public string $libelle,
        public float $plafond,
    ) {}

    public static function fromRequest(BudgetRequest $request): self
    {
        return new self(
            $request->validated('libelle'),
            $request->validated('plafond'),
        );
    }
}
