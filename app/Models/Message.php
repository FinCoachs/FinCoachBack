<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Models\User;

#[Fillable(['user_id', 'contenu', 'expediteur', 'date'])]
class Message extends Model
{
    use HasUuids;
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
