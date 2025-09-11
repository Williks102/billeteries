<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE orders MODIFY COLUMN payment_status ENUM('pending', 'processing', 'paid', 'failed', 'refunded') DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE orders MODIFY COLUMN payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending'");
    }
};