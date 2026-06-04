<?php
    namespace App\DTOs\Transaction;


    readonly class TransactionDTOs {
        public function __construct(
            public string $libelle,
            public string $montant,
            public string $date,
            public string $description,
            public string $type,
            public string $categorie_id,
            public string $compte_id,
        )
        {}
    }