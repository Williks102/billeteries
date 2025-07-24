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
        Schema::create('commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('promoteur_id')->constrained('users')->onDelete('cascade');
            $table->integer('gross_amount'); // Montant brut en FCFA
            $table->decimal('commission_rate', 5, 2); // Taux de commission (ex: 10.50%)
            $table->integer('commission_amount'); // Montant commission en FCFA
            $table->integer('net_amount'); // Montant net pour le promoteur
            $table->integer('platform_fee')->default(0); // Frais plateforme fixe
            $table->integer('payment_processor_fee')->default(0); // Frais processeur paiement
            $table->enum('status', ['pending', 'paid', 'held'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commissions');
    }
};