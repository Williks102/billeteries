<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Migration additive : notes de l'administrateur sur une commande (jusqu'ici silencieusement perdues).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'admin_notes')) {
                $table->text('admin_notes')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Volontairement vide : on ne supprime jamais de données de commande.
    }
};
