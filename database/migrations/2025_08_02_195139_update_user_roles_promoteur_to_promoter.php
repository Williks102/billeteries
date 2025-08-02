<?php
// 4. Migration pour mettre à jour les rôles utilisateurs (optionnel)
// database/migrations/2025_08_02_000004_update_user_roles_promoteur_to_promoter.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Option 1: Garder 'promoteur' dans la base pour l'affichage français
        // Rien à faire, le rôle reste 'promoteur'
        
        // Option 2: Harmoniser aussi le rôle vers l'anglais
        // Décommentez si vous voulez aussi changer les rôles
        /*
        DB::table('users')
            ->where('role', 'promoteur')
            ->update(['role' => 'promoter']);
        */
    }

    public function down(): void
    {
        // Si vous avez changé les rôles, les remettre
        /*
        DB::table('users')
            ->where('role', 'promoter')
            ->update(['role' => 'promoteur']);
        */
    }
};