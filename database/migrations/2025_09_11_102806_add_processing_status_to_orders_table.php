<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            // Autres moteurs (SQLite des tests) : ALTER ... MODIFY n'existe pas ; Laravel gère l'ENUM via ->change().
            Schema::table('orders', function (Blueprint $table) {
                $table->enum('payment_status', ['pending', 'processing', 'paid', 'failed', 'refunded'])->default('pending')->change();
            });

            return;
        }

        DB::statement("ALTER TABLE orders MODIFY COLUMN payment_status ENUM('pending', 'processing', 'paid', 'failed', 'refunded') DEFAULT 'pending'");
    }

    public function down(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            // Autres moteurs (SQLite des tests) : ALTER ... MODIFY n'existe pas ; Laravel gère l'ENUM via ->change().
            Schema::table('orders', function (Blueprint $table) {
                $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending')->change();
            });

            return;
        }

        DB::statement("ALTER TABLE orders MODIFY COLUMN payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending'");
    }
};