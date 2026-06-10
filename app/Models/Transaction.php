<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Fillable(['categorie_id', 'compte_id', 'montant', 'type', 'description', 'date'])]
class Transaction extends Model
{
    use HasFactory, HasUuids;

    public function categorie(): BelongsTo
    {
        return $this->belongsTo(Categorie::class);
    }

    public function compte(): BelongsTo
    {
        return $this->belongsTo(Compte::class);
    }
}
