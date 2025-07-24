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
        Schema::create('commission_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('event_categories')->onDelete('cascade');
            $table->foreignId('promoteur_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->decimal('commission_rate', 5, 2); // Taux en pourcentage (ex: 10.50)
            $table->integer('platform_fee_fixed')->default(0); // Frais fixe en FCFA
            $table->integer('min_commission')->default(0); // Commission minimum en FCFA
            $table->boolean('is_active')->default(true);
            $table->date('valid_from');
            $table->date('valid_until')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commission_settings');
    }
};