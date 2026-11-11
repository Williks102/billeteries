<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Migration additive uniquement : aucune colonne existante n'est modifiée ou supprimée.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'paid_at')) {
                $table->timestamp('paid_at')->nullable();
            }
            if (!Schema::hasColumn('orders', 'expires_at')) {
                $table->timestamp('expires_at')->nullable();
                $table->index(['payment_status', 'expires_at'], 'orders_status_expires_idx');
            }
        });
    }

    public function down(): void
    {
        // Volontairement vide : on ne supprime jamais de données de paiement.
    }
};
