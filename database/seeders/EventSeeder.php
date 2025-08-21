<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\User;
use App\Models\TicketType;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Récupérer les catégories et promoteurs
        $concertCategory = EventCategory::where('slug', 'concert')->first();
        $theatreCategory = EventCategory::where('slug', 'theatre')->first();
        $sportCategory = EventCategory::where('slug', 'sport')->first();
        $conferenceCategory = EventCategory::where('slug', 'conference')->first();
        $festivalCategory = EventCategory::where('slug', 'festival')->first();

        $promoteur1 = User::where('email', 'kouadio@productions.ci')->first();
        $promoteur2 = User::where('email', 'contact@abidjan-events.ci')->first();
        $promoteur3 = User::where('email', 'info@culture-spectacles.ci')->first();
        $promoteur4 = User::where('email', 'sports@abidjan.ci')->first();

        // 1. Concert Magic System
        $magicSystemEvent = Event::create([
            'promoter_id' => $promoteur1->id,
            'category_id' => $concertCategory->id,
            'title' => 'Magic System Live - Tournée "Akwaba"',
            'description' => 'Le groupe légendaire Magic System revient sur scène avec leur tournée "Akwaba" ! Venez vivre une soirée inoubliable avec Salif Traoré et son équipe. Au programme : tous leurs hits including "Premier Gaou", "Bouger Bouger" et leurs derniers succès.',
            'venue' => 'Palais de la Culture d\'Abidjan',
            'address' => 'Boulevard de la République, Abidjan, Plateau',
            'event_date' => now()->addDays(45),
            'event_time' => now()->addDays(45)->setTime(20, 0),
            'end_time' => now()->addDays(45)->setTime(23, 30),
            'status' => 'published'
        ]);

        // Types de billets pour Magic System
        TicketType::create([
            'event_id' => $magicSystemEvent->id,
            'name' => 'VIP Gold',
            'description' => 'Accès backstage, meet & greet avec les artistes, boissons premium incluses, places réservées au premier rang',
            'price' => 100000, // 100,000 FCFA
            'quantity_available' => 50,
            'quantity_sold' => 12,
            'sale_start_date' => now()->subDays(30),
            'sale_end_date' => $magicSystemEvent->event_date->subHours(2),
            'max_per_order' => 2,
            'is_active' => true
        ]);

        TicketType::create([
            'event_id' => $magicSystemEvent->id,
            'name' => 'VIP Standard',
            'description' => 'Zone VIP avec places assises, boissons incluses et buffet',
            'price' => 75000, // 75,000 FCFA
            'quantity_available' => 100,
            'quantity_sold' => 23,
            'sale_start_date' => now()->subDays(30),
            'sale_end_date' => $magicSystemEvent->event_date->subHours(2),
            'max_per_order' => 4,
            'is_active' => true
        ]);

        TicketType::create([
            'event_id' => $magicSystemEvent->id,
            'name' => 'Carré Or',
            'description' => 'Zone premium debout, très proche de la scène',
            'price' => 50000, // 50,000 FCFA
            'quantity_available' => 200,
            'quantity_sold' => 67,
            'sale_start_date' => now()->subDays(30),
            'sale_end_date' => $magicSystemEvent->event_date->subHours(1),
            'max_per_order' => 6,
            'is_active' => true
        ]);

        TicketType::create([
            'event_id' => $magicSystemEvent->id,
            'name' => 'Tribune',
            'description' => 'Places assises en tribune avec excellente vue',
            'price' => 35000, // 35,000 FCFA
            'quantity_available' => 300,
            'quantity_sold' => 89,
            'sale_start_date' => now()->subDays(30),
            'sale_end_date' => $magicSystemEvent->event_date->subHours(1),
            'max_per_order' => 8,
            'is_active' => true
        ]);

        TicketType::create([
            'event_id' => $magicSystemEvent->id,
            'name' => 'Pelouse',
            'description' => 'Accès général debout',
            'price' => 20000, // 20,000 FCFA
            'quantity_available' => 500,
            'quantity_sold' => 156,
            'sale_start_date' => now()->subDays(30),
            'sale_end_date' => $magicSystemEvent->event_date,
            'max_per_order' => 10,
            'is_active' => true
        ]);

        TicketType::create([
            'event_id' => $magicSystemEvent->id,
            'name' => 'Étudiant',
            'description' => 'Tarif spécial étudiants (carte étudiant obligatoire)',
            'price' => 15000, // 15,000 FCFA
            'quantity_available' => 150,
            'quantity_sold' => 45,
            'sale_start_date' => now()->subDays(20),
            'sale_end_date' => $magicSystemEvent->event_date,
            'max_per_order' => 2,
            'is_active' => true
        ]);

        // 2. Alpha Blondy Concert
        $alphaBlondyEvent = Event::create([
            'promoter_id' => $promoteur2->id,
            'category_id' => $concertCategory->id,
            'title' => 'Alpha Blondy & The Solar System',
            'description' => 'Le roi du reggae africain Alpha Blondy en concert exceptionnel ! Accompagné de son groupe The Solar System, il interprétera ses plus grands succès : "Brigadier Sabari", "Jerusalem", "Cocody Rock"...',
            'venue' => 'Stade Félix Houphouët-Boigny',
            'address' => 'Boulevard Lagunaire, Abidjan, Le Plateau',
            'event_date' => now()->addDays(60),
            'event_time' => now()->addDays(60)->setTime(19, 30),
            'end_time' => now()->addDays(60)->setTime(23, 0),
            'status' => 'published'
        ]);

        // Types de billets pour Alpha Blondy
        TicketType::create([
            'event_id' => $alphaBlondyEvent->id,
            'name' => 'VIP Rastafari',
            'description' => 'Zone VIP exclusive avec rencontre artiste',
            'price' => 80000, // 80,000 FCFA
            'quantity_available' => 80,
            'quantity_sold' => 15,
            'sale_start_date' => now()->subDays(25),
            'sale_end_date' => $alphaBlondyEvent->event_date->subHours(3),
            'max_per_order' => 3,
            'is_active' => true
        ]);

        TicketType::create([
            'event_id' => $alphaBlondyEvent->id,
            'name' => 'Fosse',
            'description' => 'Zone debout face à la scène',
            'price' => 40000, // 40,000 FCFA
            'quantity_available' => 400,
            'quantity_sold' => 78,
            'sale_start_date' => now()->subDays(25),
            'sale_end_date' => $alphaBlondyEvent->event_date,
            'max_per_order' => 8,
            'is_active' => true
        ]);

        TicketType::create([
            'event_id' => $alphaBlondyEvent->id,
            'name' => 'Gradin',
            'description' => 'Places assises dans les gradins',
            'price' => 25000, // 25,000 FCFA
            'quantity_available' => 600,
            'quantity_sold' => 89,
            'sale_start_date' => now()->subDays(25),
            'sale_end_date' => $alphaBlondyEvent->event_date,
            'max_per_order' => 10,
            'is_active' => true
        ]);

        // 3. Match ASEC vs Africa Sports
        $matchEvent = Event::create([
            'promoter_id' => $promoteur4->id,
            'category_id' => $sportCategory->id,
            'title' => 'ASEC Mimosas vs Africa Sports - Derby d\'Abidjan',
            'description' => 'Le derby le plus attendu de l\'année ! ASEC Mimosas reçoit Africa Sports dans un match qui s\'annonce explosif. Venez supporter votre équipe favorite dans une ambiance de folie !',
            'venue' => 'Stade Robert Champroux',
            'address' => 'Marcory, Abidjan',
            'event_date' => now()->addDays(30),
            'event_time' => now()->addDays(30)->setTime(16, 0),
            'end_time' => now()->addDays(30)->setTime(18, 0),
            'status' => 'published'
        ]);

        // Types de billets pour le match
        TicketType::create([
            'event_id' => $matchEvent->id,
            'name' => 'Tribune Présidentielle',
            'description' => 'Tribune couverte avec sièges numérotés',
            'price' => 20000, // 20,000 FCFA
            'quantity_available' => 100,
            'quantity_sold' => 34,
            'sale_start_date' => now()->subDays(15),
            'sale_end_date' => $matchEvent->event_date->subHours(2),
            'max_per_order' => 5,
            'is_active' => true
        ]);

        TicketType::create([
            'event_id' => $matchEvent->id,
            'name' => 'Tribune Latérale',
            'description' => 'Gradins latéraux avec bonne visibilité',
            'price' => 12000, // 12,000 FCFA
            'quantity_available' => 300,
            'quantity_sold' => 87,
            'sale_start_date' => now()->subDays(15),
            'sale_end_date' => $matchEvent->event_date->subHours(1),
            'max_per_order' => 8,
            'is_active' => true
        ]);

        TicketType::create([
            'event_id' => $matchEvent->id,
            'name' => 'Virage Populaire',
            'description' => 'Zone supporters debout',
            'price' => 5000, // 5,000 FCFA
            'quantity_available' => 800,
            'quantity_sold' => 234,
            'sale_start_date' => now()->subDays(15),
            'sale_end_date' => $matchEvent->event_date,
            'max_per_order' => 15,
            'is_active' => true
        ]);

        // 4. Pièce de Théâtre
        $theatreEvent = Event::create([
            'promoter_id' => $promoteur3->id,
            'category_id' => $theatreCategory->id,
            'title' => 'L\'Avare de Molière - Version Ivoirienne',
            'description' => 'Une adaptation moderne et humoristique de la célèbre pièce de Molière, transposée dans le contexte ivoirien. Mise en scène par Souleymane Koly.',
            'venue' => 'Théâtre National de Côte d\'Ivoire',
            'address' => 'Avenue Chardy, Abidjan, Plateau',
            'event_date' => now()->addDays(20),
            'event_time' => now()->addDays(20)->setTime(19, 30),
            'end_time' => now()->addDays(20)->setTime(22, 0),
            'status' => 'published'
        ]);

        // Types de billets pour le théâtre
        TicketType::create([
            'event_id' => $theatreEvent->id,
            'name' => 'Orchestre',
            'description' => 'Meilleures places au niveau orchestre',
            'price' => 25000, // 25,000 FCFA
            'quantity_available' => 80,
            'quantity_sold' => 23,
            'sale_start_date' => now()->subDays(10),
            'sale_end_date' => $theatreEvent->event_date,
            'max_per_order' => 4,
            'is_active' => true
        ]);

        TicketType::create([
            'event_id' => $theatreEvent->id,
            'name' => 'Balcon',
            'description' => 'Places en balcon avec excellente visibilité',
            'price' => 18000, // 18,000 FCFA
            'quantity_available' => 120,
            'quantity_sold' => 34,
            'sale_start_date' => now()->subDays(10),
            'sale_end_date' => $theatreEvent->event_date,
            'max_per_order' => 6,
            'is_active' => true
        ]);

        TicketType::create([
            'event_id' => $theatreEvent->id,
            'name' => 'Paradis',
            'description' => 'Places en hauteur à prix réduit',
            'price' => 10000, // 10,000 FCFA
            'quantity_available' => 100,
            'quantity_sold' => 45,
            'sale_start_date' => now()->subDays(10),
            'sale_end_date' => $theatreEvent->event_date,
            'max_per_order' => 8,
            'is_active' => true
        ]);

        // 5. Conférence Tech
        $conferenceEvent = Event::create([
            'promoter_id' => $promoteur2->id,
            'category_id' => $conferenceCategory->id,
            'title' => 'Digital Abidjan 2024 - L\'Avenir du Numérique en Afrique',
            'description' => 'La plus grande conférence tech de l\'Afrique de l\'Ouest ! Intervenants internationaux, startups, innovations. Thème principal : "IA et Blockchain pour le développement africain".',
            'venue' => 'Sofitel Abidjan Hôtel Ivoire',
            'address' => 'Boulevard de la Corniche, Abidjan, Cocody',
            'event_date' => now()->addDays(35),
            'event_time' => now()->addDays(35)->setTime(8, 30),
            'end_time' => now()->addDays(35)->setTime(18, 0),
            'status' => 'published'
        ]);

        // Types de billets pour la conférence
        TicketType::create([
            'event_id' => $conferenceEvent->id,
            'name' => 'VIP All Access',
            'description' => 'Accès complet + networking dinner + certificat',
            'price' => 150000, // 150,000 FCFA
            'quantity_available' => 50,
            'quantity_sold' => 12,
            'sale_start_date' => now()->subDays(20),
            'sale_end_date' => $conferenceEvent->event_date->subDays(3),
            'max_per_order' => 3,
            'is_active' => true
        ]);

        TicketType::create([
            'event_id' => $conferenceEvent->id,
            'name' => 'Standard',
            'description' => 'Accès aux conférences + pauses café',
            'price' => 75000, // 75,000 FCFA
            'quantity_available' => 200,
            'quantity_sold' => 45,
            'sale_start_date' => now()->subDays(20),
            'sale_end_date' => $conferenceEvent->event_date->subDays(1),
            'max_per_order' => 5,
            'is_active' => true
        ]);

        TicketType::create([
            'event_id' => $conferenceEvent->id,
            'name' => 'Étudiant',
            'description' => 'Tarif réduit pour étudiants (justificatif requis)',
            'price' => 25000, // 25,000 FCFA
            'quantity_available' => 100,
            'quantity_sold' => 34,
            'sale_start_date' => now()->subDays(15),
            'sale_end_date' => $conferenceEvent->event_date->subDays(1),
            'max_per_order' => 2,
            'is_active' => true
        ]);
    }
}