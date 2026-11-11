<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Migration additive et idempotente (audit des migrations).
 *
 * Admin\AdminController (paiement en lot des commissions, ~l.210) exécute
 * Commission::whereIn(...)->update(['status' => 'paid', 'paid_at' => ..., 'paid_by' => auth()->id()]) :
 * UPDATE direct du query builder, donc « Unknown column paid_by » si la colonne n'existe pas.
 * Type FK : unsignedBigInteger = users.id (id()).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('commissions', 'paid_by')) {
            Schema::table('commissions', function (Blueprint $table) {
                $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        // Volontairement vide : on ne supprime jamais de données financières.
    }
};
