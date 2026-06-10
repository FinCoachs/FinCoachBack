<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('notifications', 'alertes');
    }

    public function down(): void
    {
        Schema::rename('alertes', 'notifications');
    }
};
