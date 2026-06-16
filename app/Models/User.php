<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;

#[Fillable(['name', 'email', 'password', 'profil', 'budget', 'avatar', 'google_id', 'email_verified_at', 'expo_push_token'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasUuids;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    public function comptes(): HasMany
    {
        return $this->hasMany(Compte::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Categorie::class);
    }

    // transactions n'a pas de user_id → on passe par categories
    public function transactions(): HasManyThrough
    {
        return $this->hasManyThrough(Transaction::class, Categorie::class);
    }

    public function alertes(): HasMany
    {
        return $this->hasMany(Alerte::class);
    }

    public function rapportsMensuels(): HasMany
    {
        return $this->hasMany(RapportMensuel::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function transactionSummaries(): HasMany
    {
        return $this->hasMany(TransactionSummary::class);
    }

    /** Résumé le plus récent, tous types confondus. */
    public function latestTransactionSummary(): HasOne
    {
        return $this->hasOne(TransactionSummary::class)->latestOfMany('generated_at');
    }
}
