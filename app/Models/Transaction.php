<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

#[Fillable(['user_id', 'categorie_id', 'montant', 'type', 'description', 'date'])]
class Transaction extends Model
{
    use HasUuids;
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function categorie(): BelongsTo
    {
        return $this->belongsTo(Categorie::class);
    }

    public function rapportsMensuels(): BelongsToMany
    {
        return $this->belongsToMany(RapportMensuel::class, 'rapport_mensuel_transaction', 'transaction_id', 'rapport_mensuel_id');
    }
}
