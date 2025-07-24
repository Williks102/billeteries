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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('ticket_type_id')->constrained()->onDelete('cascade');
            $table->integer('quantity'); // Nombre de billets de ce type
            $table->integer('unit_price'); // Prix unitaire en FCFA
            $table->integer('total_price'); // Prix total pour cette ligne
            $table->decimal('commission_rate', 5, 2)->default(10.00); // Taux de commission
            $table->integer('commission_amount')->default(0); // Montant commission en FCFA
            $table->integer('net_amount')->default(0); // Montant net pour le promoteur
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};