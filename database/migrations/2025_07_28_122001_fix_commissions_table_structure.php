<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commissions', function (Blueprint $table) {
            // Si la colonne s'appelle 'promoteur_id', la renommer en 'promoter_id'
            if (Schema::hasColumn('commissions', 'promoteur_id') && !Schema::hasColumn('commissions', 'promoter_id')) {
                $table->renameColumn('promoteur_id', 'promoter_id');
            }
            
            // Si aucune des deux colonnes n'existe, créer promoter_id
            if (!Schema::hasColumn('commissions', 'promoter_id') && !Schema::hasColumn('commissions', 'promoteur_id')) {
                $table->foreignId('promoter_id')->after('order_id')->constrained('users')->onDelete('cascade');
            }
            
            // S'assurer que le status inclut toutes les valeurs nécessaires
            if (Schema::hasColumn('commissions', 'status')) {
                $table->enum('status', ['pending', 'paid', 'cancelled', 'held'])->default('pending')->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('commissions', function (Blueprint $table) {
            // Optionnel: logic pour revenir en arrière
            if (Schema::hasColumn('commissions', 'promoter_id')) {
                $table->renameColumn('promoter_id', 'promoteur_id');
            }
        });
    }
};