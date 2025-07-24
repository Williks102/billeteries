<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            EventCategorySeeder::class,
            UserSeeder::class,
            CommissionSettingSeeder::class,
            EventSeeder::class,
            TicketSeeder::class,
        ]);

        $this->command->info('🎉 Base de données seedée avec succès !');
        $this->command->info('');
        $this->command->info('📊 Données créées :');
        $this->command->info('- Catégories d\'événements : ' . \App\Models\EventCategory::count());
        $this->command->info('- Utilisateurs : ' . \App\Models\User::count());
        $this->command->info('- Paramètres de commission : ' . \App\Models\CommissionSetting::count());
        $this->command->info('- Événements : ' . \App\Models\Event::count());
        $this->command->info('- Types de billets : ' . \App\Models\TicketType::count());
        $this->command->info('- Billets générés : ' . \App\Models\Ticket::count());
        $this->command->info('');
        $this->command->info('👤 Comptes de test :');
        $this->command->info('Admin : admin@billetterie-ci.com / admin123');
        $this->command->info('Promoteur : promoteur@test.com / password');
        $this->command->info('Acheteur : acheteur@test.com / password');
        $this->command->info('');
        $this->command->info('🎫 Événements créés :');
        $this->command->info('- Magic System Live au Palais de la Culture');
        $this->command->info('- Alpha Blondy au Stade Félix Houphouët-Boigny');
        $this->command->info('- ASEC vs Africa Sports au Stade Champroux');
        $this->command->info('- L\'Avare de Molière au Théâtre National');
        $this->command->info('- Digital Abidjan 2024 au Sofitel Ivoire');
        $this->command->info('');
        $this->command->info('🚀 Prêt à tester l\'application !');
    }
}