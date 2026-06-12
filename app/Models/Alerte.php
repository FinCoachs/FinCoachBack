<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Fillable(['user_id', 'message', 'type', 'lue', 'date'])]
class Alerte extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'alertes';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
