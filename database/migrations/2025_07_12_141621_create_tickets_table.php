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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_type_id')->constrained()->onDelete('cascade');
            $table->string('ticket_code')->unique(); // Code unique pour chaque billet
            $table->text('qr_code')->nullable(); // QR code pour validation
            $table->enum('status', ['available', 'sold', 'used', 'cancelled'])->default('available');
            $table->string('seat_number')->nullable(); // Numéro de siège (optionnel)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};