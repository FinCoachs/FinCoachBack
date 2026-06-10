<?php

namespace App\DTOs\Comptes;

use App\Http\Requests\Compte\CompteRequest;

readonly class CreateCompteDTO
{
    public function __construct(
        public string $libelle,
        public string $numero,
    ) {}

    public static function fromRequest(CompteRequest $request): self
    {
        return new self(
            $request->validated('libelle'),
            $request->validated('numero'),
        );
    }
}
