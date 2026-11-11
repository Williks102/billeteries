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
        // Idempotence (correctif B1) : la migration 2025_07_28_122001 a déjà renommé promoteur_id en
        // promoter_id (la clé étrangère garde son ancien nom commissions_promoteur_id_foreign). Recréer
        // promoteur_id avec une clé étrangère du même nom échouerait (MySQL/TiDB : erreur 1826).
        // On ne recrée donc l'ancienne colonne que si AUCUNE des deux colonnes n'existe.
        $needsLegacyPromoterColumn = !Schema::hasColumn('commissions', 'promoteur_id')
            && !Schema::hasColumn('commissions', 'promoter_id');

        Schema::table('commissions', function (Blueprint $table) use ($needsLegacyPromoterColumn) {
            if ($needsLegacyPromoterColumn) {
                $table->unsignedBigInteger('promoteur_id')->after('order_id');
                $table->foreign('promoteur_id')->references('id')->on('users')->onDelete('cascade');
            }
            
            // Ajouter une colonne notes si elle n'existe pas
            if (!Schema::hasColumn('commissions', 'notes')) {
                $table->text('notes')->nullable()->after('paid_at');
            }
            
            // Ajouter des index pour optimiser les performances
            if (!Schema::hasIndex('commissions', 'commissions_status_index')) {
                $table->index('status');
            }
            
            // Index composite sur l'ancienne colonne : seulement si elle existe (ou vient d'être recréée).
            // Avec promoter_id, l'index équivalent est créé par 2025_08_02_195133.
            if (($needsLegacyPromoterColumn || Schema::hasColumn('commissions', 'promoteur_id'))
                && !Schema::hasIndex('commissions', 'commissions_promoteur_id_status_index')) {
                $table->index(['promoteur_id', 'status']);
            }
            
            if (!Schema::hasIndex('commissions', 'commissions_created_at_index')) {
                $table->index('created_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('commissions', function (Blueprint $table) {
            // Supprimer les index
            $table->dropIndex(['status']);
            $table->dropIndex(['promoteur_id', 'status']);
            $table->dropIndex(['created_at']);
            
            // Supprimer les colonnes ajoutées
            $table->dropColumn('notes');
            
            // Ne pas supprimer promoteur_id car elle pourrait être utilisée ailleurs
        });
    }
};