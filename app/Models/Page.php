<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Page extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'meta_title',
        'meta_description',
        'template',
        'is_active',
        'show_in_menu',
        'menu_order',
        'custom_fields'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'show_in_menu' => 'boolean',
        'custom_fields' => 'array',
    ];

    /**
     * Générer automatiquement le slug
     */
    public static function boot()
    {
        parent::boot();
        
        static::creating(function ($page) {
            if (empty($page->slug)) {
                $page->slug = Str::slug($page->title);
            }
            
            // Assurer l'unicité du slug
            $originalSlug = $page->slug;
            $counter = 1;
            
            while (static::where('slug', $page->slug)->exists()) {
                $page->slug = $originalSlug . '-' . $counter;
                $counter++;
            }
        });
    }

    /**
     * Scope pour les pages actives
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope pour les pages du menu
     */
    public function scopeInMenu($query)
    {
        return $query->where('show_in_menu', true)
                    ->orderBy('menu_order', 'asc');
    }

    /**
     * Getter pour l'URL de la page
     */
    public function getUrlAttribute()
    {
        return route('pages.show', $this->slug);
    }

    /**
     * Getter pour le titre SEO
     */
    public function getSeoTitleAttribute()
    {
        return $this->meta_title ?: $this->title;
    }

    /**
     * Getter pour la description SEO
     */
    public function getSeoDescriptionAttribute()
    {
        return $this->meta_description ?: $this->excerpt ?: Str::limit(strip_tags($this->content), 160);
    }

    /**
     * Templates disponibles
     */
    public static function getAvailableTemplates()
    {
        return [
            'default' => 'Page standard',
            'full-width' => 'Pleine largeur',
            'landing' => 'Page d\'atterrissage',
            'faq' => 'Questions/Réponses',
            'contact' => 'Contact avec formulaire',
            'pricing' => 'Tarification',
        ];
    }

    /**
     * Seeder par défaut pour les pages essentielles
     */
    public static function seedDefaultPages()
    {
        $pages = [
            [
                'title' => 'À propos',
                'slug' => 'about',
                'excerpt' => 'Découvrez l\'histoire et les valeurs de ClicBillet CI',
                'content' => '<h1>À propos de ClicBillet CI</h1><p>Contenu à personnaliser...</p>',
                'template' => 'default',
                'show_in_menu' => true,
                'menu_order' => 1,
            ],
            [
                'title' => 'Comment ça marche',
                'slug' => 'how-it-works',
                'excerpt' => 'Guide d\'utilisation de la plateforme',
                'content' => '<h1>Comment ça marche</h1><p>Contenu à personnaliser...</p>',
                'template' => 'default',
                'show_in_menu' => true,
                'menu_order' => 2,
            ],
            [
                'title' => 'FAQ',
                'slug' => 'faq',
                'excerpt' => 'Questions fréquemment posées',
                'content' => '<h1>Questions fréquentes</h1><p>Contenu à personnaliser...</p>',
                'template' => 'faq',
                'show_in_menu' => true,
                'menu_order' => 3,
            ],
            [
                'title' => 'Contact',
                'slug' => 'contact',
                'excerpt' => 'Contactez notre équipe',
                'content' => '<h1>Nous contacter</h1><p>Contenu à personnaliser...</p>',
                'template' => 'contact',
                'show_in_menu' => true,
                'menu_order' => 4,
            ],
            [
                'title' => 'Tarifs',
                'slug' => 'pricing',
                'excerpt' => 'Nos tarifs et commissions',
                'content' => '<h1>Nos tarifs</h1><p>Contenu à personnaliser...</p>',
                'template' => 'pricing',
                'show_in_menu' => false,
                'menu_order' => 5,
            ],
            [
                'title' => 'Conditions d\'utilisation',
                'slug' => 'terms',
                'excerpt' => 'Conditions générales d\'utilisation',
                'content' => '<h1>Conditions d\'utilisation</h1><p>Contenu à personnaliser...</p>',
                'template' => 'default',
                'show_in_menu' => false,
                'menu_order' => 0,
            ],
            [
                'title' => 'Politique de confidentialité',
                'slug' => 'privacy',
                'excerpt' => 'Protection de vos données personnelles',
                'content' => '<h1>Politique de confidentialité</h1><p>Contenu à personnaliser...</p>',
                'template' => 'default',
                'show_in_menu' => false,
                'menu_order' => 0,
            ]
        ];

        foreach ($pages as $pageData) {
            static::firstOrCreate(
                ['slug' => $pageData['slug']],
                $pageData
            );
        }
    }
}