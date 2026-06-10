<?php

namespace App\Http\Resources\Compte;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'      => $this->id,
            'libelle' => $this->libelle,
            'numero'  => $this->numero,
            'solde'   => (float)(($this->revenus_sum ?? 0) - ($this->depenses_sum ?? 0)),
            'date'    => $this->date,
        ];
    }
}
