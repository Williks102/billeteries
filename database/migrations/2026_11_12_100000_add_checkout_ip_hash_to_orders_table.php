<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Migration additive et idempotente (commandes gratuites).
 *
 * Une commande gratuite est payée immédiatement : le plafond « commandes en attente » ne la limite plus.
 * Le plafond par IP des commandes gratuites est donc compté en base : on mémorise, pour ces commandes
 * seulement, une empreinte HMAC de l'IP (jamais l'IP en clair). Colonne nullable : aucune donnée existante touchée.
 * payment_method est un simple string (aucun enum) : la valeur 'free' n'exige aucune migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Colonne et index protégés séparément : une exécution interrompue entre les deux se rattrape.
        if (! Schema::hasColumn('orders', 'checkout_ip_hash')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('checkout_ip_hash', 64)->nullable();
            });
        }

        if (! Schema::hasIndex('orders', ['checkout_ip_hash'])) {
            Schema::table('orders', function (Blueprint $table) {
                $table->index('checkout_ip_hash');
            });
        }
    }

    public function down(): void
    {
        // Volontairement vide : on ne supprime jamais de colonne de commandes.
    }
};
