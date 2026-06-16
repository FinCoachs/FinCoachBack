<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionSummary extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'type',
        'period_start',
        'period_end',
        'last_transaction_date',
        'last_transaction_id',
        'aggregats',
        'narratif',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'period_start'          => 'date',
            'period_end'            => 'date',
            'last_transaction_date' => 'date',
            'aggregats'             => 'array',
            'generated_at'          => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Indique si ce résumé ne couvre aucune transaction (utilisateur vide). */
    public function isEmpty(): bool
    {
        return ($this->aggregats['transaction_count'] ?? 0) === 0;
    }
}
