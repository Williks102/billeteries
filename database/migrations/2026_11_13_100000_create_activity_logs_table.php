<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Journal d'activité générique (qui a fait quoi, quand). Append-only.
 *
 * - user_id : acteur (null = système), FK nullOnDelete : supprimer un compte n'efface pas le journal ;
 * - subject_type / subject_id : sujet SANS clé étrangère (le journal survit à la suppression du sujet) ;
 * - event_id : filtre par événement, SANS clé étrangère ni cascade ;
 * - properties : valeurs avant/après uniquement (jamais de donnée personnelle) ;
 * - ip_fingerprint : HMAC de l'IP (jamais l'IP en clair) ;
 * - pas de updated_at.
 * Compatible SQLite et MySQL/TiDB (types portables uniquement).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('activity_logs')) {
            return;
        }

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_role', 30)->nullable();
            $table->string('action', 100)->index();
            $table->string('subject_type', 100)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->unsignedBigInteger('event_id')->nullable()->index();
            $table->string('description', 255);
            $table->json('properties')->nullable();
            $table->string('ip_fingerprint', 64)->nullable();
            $table->timestamp('created_at')->nullable()->index();

            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        // Volontairement vide : on ne supprime jamais un journal d'activité.
    }
};
