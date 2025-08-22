<?php
// database/seeders/EventSeeder.php - VERSION CORRIGÉE POUR CORRESPONDRE AU USERSEEDER

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Event;
use App\Models\User;
use App\Models\EventCategory;
use Carbon\Carbon;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🎪 Création des événements...');

        // Récupérer les promoteurs et catégories
        $promoters = User::where('role', 'promoter')->get();
        $categories = EventCategory::all();

        if ($promoters->isEmpty() || $categories->isEmpty()) {
            $this->command->error('❌ Aucun promoteur ou catégorie trouvé. Exécutez UserSeeder et EventCategorySeeder d\'abord.');
            return;
        }

        // Récupérer les emails exacts du UserSeeder
        $promoter1 = User::where('email', 'contact@sergebeynaud.ci')->first(); // Serge Beynaud Productions
        $promoter2 = User::where('email', 'events@zouglou.ci')->first();       // Zouglou Events
        $promoter3 = User::where('email', 'info@amf.ci')->first();             // Abidjan Music Festival
        $promoter4 = User::where('email', 'contact@sportci.com')->first();     // Sports & Entertainment CI
        $promoter5 = User::where('email', 'culture@abidjan.ci')->first();      // Cultural Events Abidjan

        // Récupérer les catégories par slug
        $musiqueCategory = EventCategory::where('slug', 'musique-concerts')->first();
        $sportsCategory = EventCategory::where('slug', 'sports-loisirs')->first();
        $cultureCategory = EventCategory::where('slug', 'culture-arts')->first();
        $gastronomieCategory = EventCategory::where('slug', 'gastronomie')->first();
        $businessCategory = EventCategory::where('slug', 'business-networking')->first();

        $events = [];

        // ===== MUSIQUE ET CONCERTS =====
        $events[] = [
            'promoter_id' => $promoter1->id,
            'category_id' => $musiqueCategory->id,
            'title' => 'Festival Zouglou 2025',
            'description' => 'Le plus grand festival de Zouglou de Côte d\'Ivoire avec les stars nationales et internationales.',
            'venue' => 'Palais de la Culture d\'Abidjan',
            'address' => 'Plateau, Abidjan',
            'event_date' => now()->addDays(45)->format('Y-m-d'),
            'event_time' => now()->addDays(45)->setTime(19, 0),
            'end_time' => now()->addDays(45)->setTime(23, 30),
            'status' => 'published',
        ];

        $events[] = [
            'promoter_id' => $promoter1->id,
            'category_id' => $musiqueCategory->id,
            'title' => 'Concert Serge Beynaud Live',
            'description' => 'Serge Beynaud en concert exceptionnel pour ses fans.',
            'venue' => 'Stade Félix Houphouët-Boigny',
            'address' => 'Abidjan',
            'event_date' => now()->addDays(60)->format('Y-m-d'),
            'event_time' => now()->addDays(60)->setTime(20, 0),
            'end_time' => now()->addDays(60)->setTime(23, 0),
            'status' => 'published',
        ];

        $events[] = [
            'promoter_id' => $promoter2->id,
            'category_id' => $musiqueCategory->id,
            'title' => 'Soirée Jazz au Sofitel',
            'description' => 'Une soirée jazz intimiste avec les meilleurs musiciens locaux.',
            'venue' => 'Sofitel Abidjan Hotel Ivoire',
            'address' => 'Cocody, Abidjan',
            'event_date' => now()->addDays(30)->format('Y-m-d'),
            'event_time' => now()->addDays(30)->setTime(20, 0),
            'end_time' => now()->addDays(30)->setTime(23, 0),
            'status' => 'published',
        ];

        $events[] = [
            'promoter_id' => $promoter3->id,
            'category_id' => $musiqueCategory->id,
            'title' => 'Abidjan Music Festival 2025',
            'description' => 'Le rendez-vous annuel des mélomanes avec les plus grands artistes africains.',
            'venue' => 'Palais des Sports de Treichville',
            'address' => 'Treichville, Abidjan',
            'event_date' => now()->addDays(90)->format('Y-m-d'),
            'event_time' => now()->addDays(90)->setTime(18, 0),
            'end_time' => now()->addDays(90)->setTime(23, 59),
            'status' => 'published',
        ];

        // ===== SPORTS ET LOISIRS =====
        $events[] = [
            'promoter_id' => $promoter4->id,
            'category_id' => $sportsCategory->id,
            'title' => 'ASEC Mimosas vs Africa Sports',
            'description' => 'Le derby d\'Abidjan tant attendu entre les deux clubs emblématiques.',
            'venue' => 'Stade Félix Houphouët-Boigny',
            'address' => 'Abidjan',
            'event_date' => now()->addDays(21)->format('Y-m-d'),
            'event_time' => now()->addDays(21)->setTime(16, 0),
            'end_time' => now()->addDays(21)->setTime(18, 0),
            'status' => 'published',
        ];

        $events[] = [
            'promoter_id' => $promoter4->id,
            'category_id' => $sportsCategory->id,
            'title' => 'Tournoi de Basketball 3x3',
            'description' => 'Tournoi de basketball urbain ouvert à tous les niveaux.',
            'venue' => 'Complexe Sportif de Marcory',
            'address' => 'Marcory, Abidjan',
            'event_date' => now()->addDays(35)->format('Y-m-d'),
            'event_time' => now()->addDays(35)->setTime(9, 0),
            'end_time' => now()->addDays(35)->setTime(18, 0),
            'status' => 'published',
        ];

        // ===== CULTURE ET ARTS =====
        $events[] = [
            'promoter_id' => $promoter5->id,
            'category_id' => $cultureCategory->id,
            'title' => 'Exposition d\'Art Contemporain Africain',
            'description' => 'Découvrez les œuvres des plus grands artistes contemporains africains.',
            'venue' => 'Centre Culturel Français',
            'address' => 'Plateau, Abidjan',
            'event_date' => now()->addDays(14)->format('Y-m-d'),
            'event_time' => now()->addDays(14)->setTime(10, 0),
            'end_time' => now()->addDays(14)->setTime(19, 0),
            'status' => 'published',
        ];

        $events[] = [
            'promoter_id' => $promoter5->id,
            'category_id' => $cultureCategory->id,
            'title' => 'Spectacle de Danse Traditionnelle',
            'description' => 'Un voyage à travers les danses traditionnelles de Côte d\'Ivoire.',
            'venue' => 'Théâtre National',
            'address' => 'Plateau, Abidjan',
            'event_date' => now()->addDays(42)->format('Y-m-d'),
            'event_time' => now()->addDays(42)->setTime(19, 30),
            'end_time' => now()->addDays(42)->setTime(21, 30),
            'status' => 'published',
        ];

        // ===== GASTRONOMIE =====
        $events[] = [
            'promoter_id' => $promoter3->id,
            'category_id' => $gastronomieCategory->id,
            'title' => 'Festival de la Gastronomie Ivoirienne',
            'description' => 'Découvrez les saveurs authentiques de la cuisine ivoirienne.',
            'venue' => 'Parc des Expositions',
            'address' => 'Zone 4C, Abidjan',
            'event_date' => now()->addDays(28)->format('Y-m-d'),
            'event_time' => now()->addDays(28)->setTime(11, 0),
            'end_time' => now()->addDays(28)->setTime(22, 0),
            'status' => 'published',
        ];

        // ===== BUSINESS ET NETWORKING =====
        $events[] = [
            'promoter_id' => $promoter4->id,
            'category_id' => $businessCategory->id,
            'title' => 'Conférence Tech & Innovation Abidjan',
            'description' => 'Rencontrez les leaders de la tech en Afrique de l\'Ouest.',
            'venue' => 'Hôtel Pullman Abidjan',
            'address' => 'Plateau, Abidjan',
            'event_date' => now()->addDays(56)->format('Y-m-d'),
            'event_time' => now()->addDays(56)->setTime(8, 30),
            'end_time' => now()->addDays(56)->setTime(17, 30),
            'status' => 'published',
        ];

        $events[] = [
            'promoter_id' => $promoter5->id,
            'category_id' => $businessCategory->id,
            'title' => 'Salon de l\'Entrepreneuriat Féminin',
            'description' => 'Réseau et formation pour les femmes entrepreneures.',
            'venue' => 'Centre de Conférences de l\'Hôtel Ivoire',
            'address' => 'Cocody, Abidjan',
            'event_date' => now()->addDays(70)->format('Y-m-d'),
            'event_time' => now()->addDays(70)->setTime(9, 0),
            'end_time' => now()->addDays(70)->setTime(16, 0),
            'status' => 'published',
        ];

        // Créer tous les événements
        foreach ($events as $eventData) {
            Event::create($eventData);
        }

        $this->command->info('✅ ' . count($events) . ' événements créés');
    }
}