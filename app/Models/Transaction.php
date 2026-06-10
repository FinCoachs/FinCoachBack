<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

#[Fillable(['categorie_id', 'montant', 'type', 'description', 'date'])]
class Transaction extends Model
{
    use HasUuids;

    public function categorie(): BelongsTo
    {
        return $this->belongsTo(Categorie::class);
    }

    public function rapportMensuel(): HasOne
    {
        return $this->hasOne(RapportMensuel::class);
    }
}
