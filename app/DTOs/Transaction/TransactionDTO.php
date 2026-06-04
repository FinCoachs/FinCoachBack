<?php

namespace App\DTOs\Transaction;

readonly class TransactionDTO
{
    public function __construct(
        public string $libelle,
        public float $montant,
        public string $date,
        public ?string $description,
        public string $type,
        public string $categorie_id,
    ) {}
}