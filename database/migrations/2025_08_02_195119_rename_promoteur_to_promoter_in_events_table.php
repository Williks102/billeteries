<?php
// 1. Migration pour la table EVENTS
// database/migrations/2025_08_02_000001_rename_promoteur_to_promoter_in_events_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            if (Schema::hasColumn('events', 'promoteur_id') && !Schema::hasColumn('events', 'promoter_id')) {
                $table->renameColumn('promoteur_id', 'promoter_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            if (Schema::hasColumn('events', 'promoter_id')) {
                $table->renameColumn('promoter_id', 'promoteur_id');
            }
        });
    }
};