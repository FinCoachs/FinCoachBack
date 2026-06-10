<?php

namespace App\Http\Resources\Compte;

use App\Services\Compte\CompteService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $service = app(CompteService::class);

        return [
            'id'      => $this->id,
            'libelle' => $this->libelle,
            'numero'  => $this->numero,
            'solde'   => $service->solde($this->resource),
            'date'    => $this->date,
        ];
    }
}
