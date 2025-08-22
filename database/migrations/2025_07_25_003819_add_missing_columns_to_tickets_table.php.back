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
        Schema::table('tickets', function (Blueprint $table) {
            // Informations du détenteur du billet (MANQUANTES)
            $table->string('holder_name')->nullable()->after('validation_data');
            $table->string('holder_email')->nullable()->after('holder_name');
            $table->string('holder_phone')->nullable()->after('holder_email');
            
            // Tracking (MANQUANTES)
            $table->ipAddress('created_ip')->nullable()->after('holder_phone');
            $table->timestamp('sent_at')->nullable()->after('created_ip');
            $table->timestamp('downloaded_at')->nullable()->after('sent_at');
            $table->integer('download_count')->default(0)->after('downloaded_at');
            
            // Relation avec les commandes (MANQUANTE)
            $table->foreignId('order_item_id')->nullable()->after('ticket_type_id')->constrained()->onDelete('set null');
        });
        
        // Ajouter les index pour les performances
        Schema::table('tickets', function (Blueprint $table) {
            $table->index(['used_at']);
            $table->index(['status', 'used_at']);
            $table->index(['holder_email']);
            $table->index(['ticket_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Supprimer les index
            $table->dropIndex(['used_at']);
            $table->dropIndex(['status', 'used_at']);
            $table->dropIndex(['holder_email']);
            $table->dropIndex(['ticket_code']);
            
            // Supprimer la contrainte de clé étrangère
            $table->dropForeign(['order_item_id']);
            
            // Supprimer les nouvelles colonnes
            $table->dropColumn([
                'holder_name',
                'holder_email', 
                'holder_phone',
                'created_ip',
                'sent_at',
                'downloaded_at',
                'download_count',
                'order_item_id'
            ]);
        });
    }
};