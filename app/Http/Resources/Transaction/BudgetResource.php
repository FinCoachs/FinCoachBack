<?php

namespace App\Http\Resources\Transaction;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BudgetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $plafond  = (float) $this->plafond;
        $depenses = (float) ($this->depenses_sum ?? 0);

        return [
            'id'          => $this->id,
            'libelle'     => $this->libelle,
            'plafond'     => $plafond,
            'depenses'    => $depenses,
            'pourcentage' => $plafond > 0 ? round(($depenses * 100) / $plafond, 2) : 0.0,
        ];
    }
}
