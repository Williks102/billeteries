<?php
// 2. Migration pour la table COMMISSIONS (complète la migration existante)
// database/migrations/2025_08_02_000002_complete_commissions_promoter_rename.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commissions', function (Blueprint $table) {
            // Cette migration complète celle qui existe déjà
            // Renommer promoteur_id vers promoter_id si pas encore fait
            if (Schema::hasColumn('commissions', 'promoteur_id') && !Schema::hasColumn('commissions', 'promoter_id')) {
                $table->renameColumn('promoteur_id', 'promoter_id');
            }
            
            // Ajouter le status 'cancelled' si pas présent
            if (Schema::hasColumn('commissions', 'status')) {
                $table->enum('status', ['pending', 'paid', 'cancelled', 'held'])->default('pending')->change();
            }
            
            // Optimiser les index avec le nouveau nom
            if (!Schema::hasIndex('commissions', 'commissions_promoter_id_status_index')) {
                $table->index(['promoter_id', 'status'], 'commissions_promoter_id_status_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('commissions', function (Blueprint $table) {
            if (Schema::hasColumn('commissions', 'promoter_id')) {
                $table->renameColumn('promoter_id', 'promoteur_id');
            }
            
            $table->dropIndex('commissions_promoter_id_status_index');
        });
    }
};
