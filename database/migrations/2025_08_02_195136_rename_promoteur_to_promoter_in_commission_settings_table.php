<?php
// 3. Migration pour la table COMMISSION_SETTINGS
// database/migrations/2025_08_02_000003_rename_promoteur_to_promoter_in_commission_settings_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commission_settings', function (Blueprint $table) {
            if (Schema::hasColumn('commission_settings', 'promoteur_id') && !Schema::hasColumn('commission_settings', 'promoter_id')) {
                $table->renameColumn('promoteur_id', 'promoter_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('commission_settings', function (Blueprint $table) {
            if (Schema::hasColumn('commission_settings', 'promoter_id')) {
                $table->renameColumn('promoter_id', 'promoteur_id');
            }
        });
    }
};