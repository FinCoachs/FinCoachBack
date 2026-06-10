<?php

namespace App\Providers;

use App\Models\Categorie;
use App\Policies\Transaction\BudgetPolicies;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Gate::policy(Categorie::class, BudgetPolicies::class);
    }
}
