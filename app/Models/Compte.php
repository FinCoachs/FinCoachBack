<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\TypeCompte;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

#[Fillable(['user_id', 'solde'])]
class Compte extends Model{
    use HasUuids;

    public function user(): BelongsTo{
        return $this->belongsTo(User::class);
    }

    public function typeComptes(): HasMany{
        return $this->hasMany(TypeCompte::class);
    }

}
