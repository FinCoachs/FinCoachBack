<?php

namespace App\DTOs\Comptes;

use App\Http\Requests\Compte\CompteRequest;

readonly class CreateCompteDTO
{
    public function __construct(
        public string $libelle,
        public string $numero,
        public float  $solde_initial = 0,
    ) {}

    public static function fromRequest(CompteRequest $request): self
    {
        return new self(
            $request->validated('libelle'),
            $request->validated('numero'),
            (float) ($request->validated('solde_initial') ?? 0),
        );
    }
}
