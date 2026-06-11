<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        if (Schema::hasTable('notifications')) {
            Schema::rename('notifications', 'alertes');
        }
    }

    public function down(): void
    {
        Schema::rename('alertes', 'notifications');
    }
};
