<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    DB::statement("ALTER TABLE tickets MODIFY COLUMN status ENUM('available', 'sold', 'used', 'cancelled', 'reserved') DEFAULT 'available'");
}

public function down()
{
    DB::statement("ALTER TABLE tickets MODIFY COLUMN status ENUM('available', 'sold', 'used', 'cancelled') DEFAULT 'available'");
}
};
