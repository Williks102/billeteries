<?php
// database/migrations/2025_08_02_cleanup_obsolete_promoteur_id_column.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Nettoyer la table commissions
        if (Schema::hasColumn('commissions', 'promoteur_id')) {
            Schema::table('commissions', function (Blueprint $table) {
                // Supprimer la contrainte de clé étrangère si elle existe
                try {
                    $table->dropForeign(['promoteur_id']);
                } catch (\Exception $e) {
                    // Ignorer si la contrainte n'existe pas
                }
                
                // Supprimer la colonne obsolète
                $table->dropColumn('promoteur_id');
            });
        }
        
        // Les tables events et commission_settings sont déjà propres avec seulement promoter_id
        
        // S'assurer que les contraintes de clés étrangères sont en place pour promoter_id
        // Les try/catch ci-dessus sont sans effet (les commandes du Blueprint s'exécutent après la closure) :
        // on teste donc explicitement. Après le renommage 2025_07_28_122001, la clé étrangère d'origine
        // (commissions_promoteur_id_foreign) couvre déjà promoter_id : ne pas en ajouter une seconde.
        // Si l'inspection des clés étrangères échoue (moteur exotique), on suppose qu'elle existe : une clé
        // redondante en moins est sans conséquence, un échec de migration au premier démarrage serait coûteux.
        try {
            $hasPromoterForeignKey = collect(Schema::getForeignKeys('commissions'))
                ->contains(fn ($fk) => $fk['columns'] === ['promoter_id']);
        } catch (\Throwable $e) {
            $hasPromoterForeignKey = true;
        }

        if (Schema::hasColumn('commissions', 'promoter_id') && !$hasPromoterForeignKey) {
            Schema::table('commissions', function (Blueprint $table) {
                $table->foreign('promoter_id')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        // En cas de rollback, recréer la colonne promoteur_id (optionnel)
        Schema::table('commissions', function (Blueprint $table) {
            $table->unsignedBigInteger('promoteur_id')->default(0)->after('order_id');
        });
        
        // Copier les données de promoter_id vers promoteur_id
        DB::statement('UPDATE commissions SET promoteur_id = promoter_id');
    }
};