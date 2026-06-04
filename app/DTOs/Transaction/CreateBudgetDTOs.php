<?php
    namespace App\DTOs\Transaction;
    use App\Http\Requests\BudgetRequest;

    readonly class CreateBudgetDTOs {
        public function __construct(
            public string $libelle,
            public string $plafond,
        )
        {}

        public static function FromValidation(BudgetRequest $request){
            return new self(
                $request->validated('libelle'),
                $request->validated('plafond')
            );
        }
    }
