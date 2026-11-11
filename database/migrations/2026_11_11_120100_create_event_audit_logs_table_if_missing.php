<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Migration additive et idempotente (audit des migrations).
 *
 * 2025_08_22_113552_2025_08_22_000002_create_event_audit_logs_table est vide : la table event_audit_logs
 * n'existait pas, alors que App\Models\EventAuditLog (colonnes de $fillable) est interrogé par
 * Admin\EventController::hybridStats (EventAuditLog::where('action', ...)). On la crée si absente.
 * Types FK : unsignedBigInteger = events.id / users.id (id()).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('event_audit_logs')) {
            return;
        }

        Schema::create('event_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 100);
            $table->text('description')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['event_id', 'created_at']);
            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        // Volontairement vide : on ne supprime jamais de journal d'audit.
    }
};
