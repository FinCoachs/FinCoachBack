<?php

namespace App\Http\Resources\Compte;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this-> id,
            'libelle' => $this->libelle,
            'numero' => $this->numero,
        ];
    }
}
