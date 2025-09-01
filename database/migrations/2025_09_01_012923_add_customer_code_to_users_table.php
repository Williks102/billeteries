<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('customer_code', 20)->unique()->nullable()->after('id');
            $table->index('customer_code'); // Pour les recherches rapides
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['customer_code']);
            $table->dropColumn('customer_code');
        });
    }
};