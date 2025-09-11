<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->string('payment_method'); // paiementpro, etc.
            $table->integer('amount'); // Montant en FCFA
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled'])
                  ->default('pending');
            $table->string('provider_transaction_id')->nullable(); // SessionId de PaiementPro
            $table->json('provider_response')->nullable(); // Réponse complète du provider
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->index(['order_id', 'payment_method']);
            $table->index('provider_transaction_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
