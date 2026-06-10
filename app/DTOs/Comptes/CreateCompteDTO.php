<?php

    namespace App\DTOs\Comptes;

use App\Http\Requests\Compte\CompteRequest;

    class CreateCompteDTO{
        public function __construct(
            public string $libelle,
            public string $numero
        ){}

        public static function fromRequest(CompteRequest $request): self{
            return new self(
                $request->libelle,
                $request->numero
            );
        }
    }
