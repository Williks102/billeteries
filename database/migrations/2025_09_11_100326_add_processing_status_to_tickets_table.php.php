<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            // Autres moteurs (SQLite des tests) : ALTER ... MODIFY n'existe pas ; Laravel gère l'ENUM via ->change().
            Schema::table('tickets', function (Blueprint $table) {
                $table->enum('status', ['available', 'sold', 'used', 'cancelled', 'reserved', 'processing'])->default('available')->change();
            });

            return;
        }

        DB::statement("ALTER TABLE tickets MODIFY COLUMN status ENUM('available', 'sold', 'used', 'cancelled', 'reserved', 'processing') DEFAULT 'available'");
}

public function down()
{
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            // Autres moteurs (SQLite des tests) : ALTER ... MODIFY n'existe pas ; Laravel gère l'ENUM via ->change().
            Schema::table('tickets', function (Blueprint $table) {
                $table->enum('status', ['available', 'sold', 'used', 'cancelled', 'reserved'])->default('available')->change();
            });

            return;
        }

        DB::statement("ALTER TABLE tickets MODIFY COLUMN status ENUM('available', 'sold', 'used', 'cancelled', 'reserved') DEFAULT 'available'");
}
};
