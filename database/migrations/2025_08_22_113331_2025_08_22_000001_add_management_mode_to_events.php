<?php
// database/migrations/2025_08_22_000001_add_management_mode_to_events.php

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
        Schema::table('events', function (Blueprint $table) {
            // Mode de gestion de l'événement
            $table->enum('management_mode', [
                'promoter',      // Promoteur gère tout (défaut)
                'admin',         // Admin gère tout
                'collaborative'  // Gestion partagée
            ])->default('promoter')->after('status');
            
            // Permissions spécifiques pour l'admin
            $table->json('admin_permissions')->nullable()->after('management_mode');
            
            // Raison du changement de mode (traçabilité)
            $table->text('management_reason')->nullable()->after('admin_permissions');
            
            // Qui a changé le mode et quand
            $table->foreignId('management_changed_by')->nullable()
                  ->after('management_reason')
                  ->constrained('users')
                  ->nullOnDelete();
            
            $table->timestamp('management_changed_at')->nullable()->after('management_changed_by');
        });

        // Index pour les performances
        Schema::table('events', function (Blueprint $table) {
            $table->index('management_mode');
            $table->index(['management_mode', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // Supprimer les index
            $table->dropIndex(['management_mode']);
            $table->dropIndex(['management_mode', 'status']);
            
            // Supprimer la contrainte de clé étrangère
            $table->dropConstrainedForeignId('management_changed_by');
            
            // Supprimer les colonnes
            $table->dropColumn([
                'management_mode',
                'admin_permissions', 
                'management_reason',
                'management_changed_at'
            ]);
        });
    }
};