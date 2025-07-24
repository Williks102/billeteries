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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Acheteur
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->integer('total_amount'); // Montant total en FCFA
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            $table->string('payment_method')->nullable(); // cinetpay, orange_money, mtn_money
            $table->string('payment_reference')->nullable(); // Référence du paiement
            $table->string('order_number')->unique(); // Numéro de commande unique
            $table->string('billing_email');
            $table->string('billing_phone')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};