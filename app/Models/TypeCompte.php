<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use App\Models\Compte;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

#[Fillable(['compte_id', 'libelle', 'date', 'type'])]
class TypeCompte extends Model
{
    use HasUuids;

    public function compte(): BelongsTo{
        return $this->belongsTo(Compte::class);
    }

}
