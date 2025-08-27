<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ajouter les colonnes pour les utilisateurs invités
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_guest')->default(false)->after('role');
            $table->timestamp('guest_converted_at')->nullable()->after('is_guest');
        });

        // Ajouter les colonnes pour les commandes invités
        Schema::table('orders', function (Blueprint $table) {
            $table->string('guest_token', 32)->nullable()->after('order_number');
            $table->index('guest_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_guest', 'guest_converted_at']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['guest_token']);
            $table->dropColumn('guest_token');
        });
    }
};