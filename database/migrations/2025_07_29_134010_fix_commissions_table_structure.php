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
        Schema::table('commissions', function (Blueprint $table) {
            // Vérifier si la colonne promoteur_id existe, sinon la créer
            if (!Schema::hasColumn('commissions', 'promoteur_id')) {
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
            
            if (!Schema::hasIndex('commissions', 'commissions_promoteur_id_status_index')) {
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