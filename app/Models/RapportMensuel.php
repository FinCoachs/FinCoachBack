<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

#[Fillable(['transaction_id', 'description', 'mois'])]
class RapportMensuel extends Model
{
    use HasUuids;

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
