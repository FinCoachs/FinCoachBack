<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Fillable(['user_id', 'libelle', 'solde', 'numero', 'date'])]
class Compte extends Model
{
    use HasFactory, HasUuids;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function revenus(): HasMany
    {
        return $this->hasMany(Transaction::class)->where('type', 'revenu');
    }

    public function depenses(): HasMany
    {
        return $this->hasMany(Transaction::class)->where('type', 'depense');
    }
}
