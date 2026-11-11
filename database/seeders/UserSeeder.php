<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    /** @var array<string,string> */
    private array $passwords = [];

    /**
     * Mot de passe issu de l'env, sinon généré aléatoirement (jamais affiché).
     */
    private function password(string $envKey): string
    {
        return $this->passwords[$envKey] ??= (env($envKey) ?: Str::random(32));
    }

    public function run(): void
    {
        // Comptes de démo : jamais en production
        if (app()->environment('production')) {
            return;
        }

        // Admin principal pour la gestion
        User::create([
            'name' => 'Admin Principal',
            'email' => 'admin@clicbillet.ci',
            'password' => Hash::make($this->password('SEED_ADMIN_PASSWORD')),
            'role' => 'admin',
            'phone' => '+225 01 02 03 04 05'
        ]);

        // Promoteurs réalistes
        User::create([
            'name' => 'Kouadio Productions',
            'email' => 'contact@kouadio-prod.ci',
            'password' => Hash::make($this->password('SEED_PROMOTEUR_PASSWORD')),
            'role' => 'promoteur',
            'phone' => '+225 07 12 34 56 78'
        ]);

        User::create([
            'name' => 'Abidjan Events',
            'email' => 'info@abidjan-events.ci',
            'password' => Hash::make($this->password('SEED_PROMOTEUR_PASSWORD')),
            'role' => 'promoteur',
            'phone' => '+225 05 67 89 01 23'
        ]);

        User::create([
            'name' => 'Culture & Spectacles CI',
            'email' => 'booking@culture-spectacles.ci',
            'password' => Hash::make($this->password('SEED_PROMOTEUR_PASSWORD')),
            'role' => 'promoteur',
            'phone' => '+225 01 45 67 89 01'
        ]);

        User::create([
            'name' => 'Sports Events Abidjan',
            'email' => 'contact@sports-abidjan.ci',
            'password' => Hash::make($this->password('SEED_PROMOTEUR_PASSWORD')),
            'role' => 'promoteur',
            'phone' => '+225 07 98 76 54 32'
        ]);

        // Acheteurs réalistes
        User::create([
            'name' => 'Aminata Traoré',
            'email' => 'aminata.traore@gmail.com',
            'password' => Hash::make($this->password('SEED_ACHETEUR_PASSWORD')),
            'role' => 'acheteur',
            'phone' => '+225 05 11 22 33 44'
        ]);

        User::create([
            'name' => 'Kofi Asante',
            'email' => 'kofi.asante@outlook.com',
            'password' => Hash::make($this->password('SEED_ACHETEUR_PASSWORD')),
            'role' => 'acheteur',
            'phone' => '+225 07 55 66 77 88'
        ]);

        User::create([
            'name' => 'Marie-Claire Brou',
            'email' => 'marie.brou@yahoo.fr',
            'password' => Hash::make($this->password('SEED_ACHETEUR_PASSWORD')),
            'role' => 'acheteur',
            'phone' => '+225 01 99 88 77 66'
        ]);

        User::create([
            'name' => 'Ibrahim Sanogo',
            'email' => 'ibrahim.sanogo@gmail.com',
            'password' => Hash::make($this->password('SEED_ACHETEUR_PASSWORD')),
            'role' => 'acheteur',
            'phone' => '+225 05 44 33 22 11'
        ]);

        User::create([
            'name' => 'Fatoumata Keita',
            'email' => 'fatoumata.keita@hotmail.fr',
            'password' => Hash::make($this->password('SEED_ACHETEUR_PASSWORD')),
            'role' => 'acheteur',
            'phone' => '+225 07 77 88 99 00'
        ]);

        // Quelques autres acheteurs pour les statistiques
        User::create([
            'name' => 'Yves Kouassi',
            'email' => 'yves.kouassi@gmail.com',
            'password' => Hash::make($this->password('SEED_ACHETEUR_PASSWORD')),
            'role' => 'acheteur',
            'phone' => '+225 01 11 22 33 44'
        ]);

        User::create([
            'name' => 'Adjoa Mensah',
            'email' => 'adjoa.mensah@yahoo.com',
            'password' => Hash::make($this->password('SEED_ACHETEUR_PASSWORD')),
            'role' => 'acheteur',
            'phone' => '+225 05 55 66 77 88'
        ]);
    }
}