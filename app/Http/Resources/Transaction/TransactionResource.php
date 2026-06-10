<?php

namespace App\Http\Resources\Transaction;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'montant'     => (float) $this->montant,
            'type'        => $this->type,
            'description' => $this->description,
            'date'        => $this->date,
            'categorie'   => $this->whenLoaded('categorie', fn() => [
                'id'      => $this->categorie->id,
                'libelle' => $this->categorie->libelle,
            ]),
        ];
    }
}
