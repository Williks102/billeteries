<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\EventCategory;

class EventCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Concert',
                'slug' => 'concert',
                'description' => 'Concerts musicaux, artistes locaux et internationaux',
                'icon' => 'fas fa-music'
            ],
            [
                'name' => 'Théâtre',
                'slug' => 'theatre',
                'description' => 'Pièces de théâtre, spectacles dramatiques',
                'icon' => 'fas fa-theater-masks'
            ],
            [
                'name' => 'Sport',
                'slug' => 'sport',
                'description' => 'Événements sportifs, matchs, compétitions',
                'icon' => 'fas fa-football-ball'
            ],
            [
                'name' => 'Conférence',
                'slug' => 'conference',
                'description' => 'Conférences, séminaires, formations',
                'icon' => 'fas fa-presentation'
            ],
            [
                'name' => 'Festival',
                'slug' => 'festival',
                'description' => 'Festivals culturels, événements traditionnels',
                'icon' => 'fas fa-calendar-star'
            ],
            [
                'name' => 'Cinéma',
                'slug' => 'cinema',
                'description' => 'Projections, avant-premières, festivals de films',
                'icon' => 'fas fa-film'
            ],
            [
                'name' => 'Danse',
                'slug' => 'danse',
                'description' => 'Spectacles de danse, ballets, performances',
                'icon' => 'fas fa-music'
            ],
            [
                'name' => 'Stand-up',
                'slug' => 'stand-up',
                'description' => 'Spectacles d\'humour, one-man-show',
                'icon' => 'fas fa-laugh'
            ],
            [
                'name' => 'Gastronomie',
                'slug' => 'gastronomie',
                'description' => 'Événements culinaires, dégustations',
                'icon' => 'fas fa-utensils'
            ],
            [
                'name' => 'Autre',
                'slug' => 'autre',
                'description' => 'Autres types d\'événements',
                'icon' => 'fas fa-star'
            ],
        ];

        foreach ($categories as $category) {
            EventCategory::create($category);
        }
    }
}