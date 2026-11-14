<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Rapports de violation CSP (mode « rapport seulement »), agrégés. Aucune donnée personnelle.
 *
 * Une ligne = une combinaison (directive violée, ressource bloquée, page) avec un compteur :
 * - directive : ex. script-src-attr ; blocked : hôte seulement, ou inline / eval / data / blob ;
 * - document_path : modèle de route de la page (ex. /events/{event}), jamais de query string ni de fragment ;
 * - key_hash : SHA-256 de (directive, blocked, document_path), clé unique (colonnes courtes : portable MySQL/TiDB).
 * Compatible SQLite et MySQL/TiDB.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('csp_violations')) {
            return;
        }

        Schema::create('csp_violations', function (Blueprint $table) {
            $table->id();
            $table->string('key_hash', 64)->unique();
            $table->string('directive', 40);
            $table->string('blocked', 100);
            $table->string('document_path', 150);
            $table->unsignedBigInteger('hits')->default(1);
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        // Volontairement vide : on ne supprime pas les données d'observation.
    }
};
