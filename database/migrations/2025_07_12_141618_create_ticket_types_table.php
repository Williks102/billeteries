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
        Schema::create('ticket_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->string('name'); // VIP, Standard, Étudiant, etc.
            $table->text('description')->nullable();
            $table->integer('price'); // Prix en FCFA (entier pour éviter les problèmes de float)
            $table->integer('quantity_available');
            $table->integer('quantity_sold')->default(0);
            $table->dateTime('sale_start_date');
            $table->dateTime('sale_end_date');
            $table->integer('max_per_order')->default(10);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_types');
    }
};