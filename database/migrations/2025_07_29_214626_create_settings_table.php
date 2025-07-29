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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // platform_name, commission_rate, etc.
            $table->text('value')->nullable(); // La valeur du paramètre
            $table->string('type')->default('string'); // string, boolean, integer, decimal
            $table->text('description')->nullable(); // Description du paramètre
            $table->string('group')->default('general'); // general, email, payment, etc.
            $table->boolean('is_public')->default(false); // Accessible côté public
            $table->timestamps();
        });
        
        // Insérer les paramètres par défaut
        DB::table('settings')->insert([
            [
                'key' => 'platform_name',
                'value' => 'ClicBillet CI',
                'type' => 'string',
                'description' => 'Nom de la plateforme',
                'group' => 'general',
                'is_public' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'platform_email',
                'value' => 'contact@clicbillet.ci',
                'type' => 'string',
                'description' => 'Email de contact de la plateforme',
                'group' => 'general',
                'is_public' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'commission_rate',
                'value' => '10.0',
                'type' => 'decimal',
                'description' => 'Taux de commission en pourcentage',
                'group' => 'financial',
                'is_public' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'currency',
                'value' => 'FCFA',
                'type' => 'string',
                'description' => 'Devise utilisée',
                'group' => 'general',
                'is_public' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'timezone',
                'value' => 'Africa/Abidjan',
                'type' => 'string',
                'description' => 'Fuseau horaire',
                'group' => 'general',
                'is_public' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'maintenance_mode',
                'value' => '0',
                'type' => 'boolean',
                'description' => 'Mode maintenance activé',
                'group' => 'system',
                'is_public' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'registration_enabled',
                'value' => '1',
                'type' => 'boolean',
                'description' => 'Inscription ouverte',
                'group' => 'system',
                'is_public' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'auto_approval_events',
                'value' => '0',
                'type' => 'boolean',
                'description' => 'Approbation automatique des événements',
                'group' => 'system',
                'is_public' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'email_notifications',
                'value' => '1',
                'type' => 'boolean',
                'description' => 'Notifications email activées',
                'group' => 'notifications',
                'is_public' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'sms_notifications',
                'value' => '0',
                'type' => 'boolean',
                'description' => 'Notifications SMS activées',
                'group' => 'notifications',
                'is_public' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};