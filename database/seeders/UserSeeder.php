<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin principal
        User::create([
            'name' => 'Admin Principal',
            'email' => 'admin@billetterie-ci.com',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'phone' => '+225 01 02 03 04 05'
        ]);

        // Promoteurs
        User::create([
            'name' => 'Kouadio Productions',
            'email' => 'kouadio@productions.ci',
            'password' => Hash::make('password123'),
            'role' => 'promoteur',
            'phone' => '+225 07 12 34 56 78'
        ]);

        User::create([
            'name' => 'Abidjan Events',
            'email' => 'contact@abidjan-events.ci',
            'password' => Hash::make('password123'),
            'role' => 'promoteur',
            'phone' => '+225 05 67 89 01 23'
        ]);

        User::create([
            'name' => 'Culture & Spectacles CI',
            'email' => 'info@culture-spectacles.ci',
            'password' => Hash::make('password123'),
            'role' => 'promoteur',
            'phone' => '+225 01 45 67 89 01'
        ]);

        User::create([
            'name' => 'Sports Events Abidjan',
            'email' => 'sports@abidjan.ci',
            'password' => Hash::make('password123'),
            'role' => 'promoteur',
            'phone' => '+225 07 98 76 54 32'
        ]);

        // Acheteurs
        User::create([
            'name' => 'Aminata Traoré',
            'email' => 'aminata@gmail.com',
            'password' => Hash::make('password123'),
            'role' => 'acheteur',
            'phone' => '+225 05 11 22 33 44'
        ]);

        User::create([
            'name' => 'Kofi Asante',
            'email' => 'kofi@yahoo.fr',
            'password' => Hash::make('password123'),
            'role' => 'acheteur',
            'phone' => '+225 07 55 66 77 88'
        ]);

        User::create([
            'name' => 'Marie-Claire Brou',
            'email' => 'marie.brou@outlook.com',
            'password' => Hash::make('password123'),
            'role' => 'acheteur',
            'phone' => '+225 01 99 88 77 66'
        ]);

        User::create([
            'name' => 'Ibrahim Sanogo',
            'email' => 'ibrahim.sanogo@gmail.com',
            'password' => Hash::make('password123'),
            'role' => 'acheteur',
            'phone' => '+225 05 44 33 22 11'
        ]);

        User::create([
            'name' => 'Fatoumata Keita',
            'email' => 'fatoumata@hotmail.fr',
            'password' => Hash::make('password123'),
            'role' => 'acheteur',
            'phone' => '+225 07 77 88 99 00'
        ]);

        // Promoteur et acheteur de test avec des logins simples
        User::create([
            'name' => 'Test Promoteur',
            'email' => 'promoteur@test.com',
            'password' => Hash::make('password'),
            'role' => 'promoteur',
            'phone' => '+225 01 23 45 67 89'
        ]);

        User::create([
            'name' => 'Test Acheteur',
            'email' => 'acheteur@test.com',
            'password' => Hash::make('password'),
            'role' => 'acheteur',
            'phone' => '+225 07 89 01 23 45'
        ]);
    }
}