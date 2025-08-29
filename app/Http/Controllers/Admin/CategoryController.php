<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EventCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
    }

    /**
     * Liste des catégories
     */
    public function index(Request $request)
    {
        $query = EventCategory::withCount('events');

        // Filtres
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        // SUPPRIMÉ LE FILTRE is_active car le champ n'existe pas
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                // Filtrer les catégories qui ont des événements actifs
                $query->withActiveEvents();
            } elseif ($request->status === 'empty') {
                // Filtrer les catégories sans événements
                $query->doesntHave('events');
            }
        }

        // Tri par nom (plus de display_order car il n'existe pas non plus)
        $categories = $query->orderBy('name')->paginate(20);

        // Statistiques CORRIGÉES
        $stats = [
            'total' => EventCategory::count(),
            'with_events' => EventCategory::has('events')->count(),
            'empty_categories' => EventCategory::doesntHave('events')->count(),
            'active_with_published_events' => EventCategory::withActiveEvents()->count(),
            'total_events' => EventCategory::withCount('events')->get()->sum('events_count'),
        ];

        return view('admin.categories.index', compact('categories', 'stats'));
    }

    /**
     * Formulaire de création catégorie
     */
    public function create()
    {
        return view('admin.categories.create');
    }

    /**
     * Sauvegarde nouvelle catégorie
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:event_categories,name',
            'description' => 'nullable|string|max:1000',
            'slug' => 'nullable|string|max:255|unique:event_categories,slug',
            'icon' => 'nullable|string|max:100',
        ]);

        try {
            $data = $request->only([
                'name', 'description', 'icon'
            ]);

            // Générer le slug s'il n'est pas fourni
            $data['slug'] = $request->slug ? 
                Str::slug($request->slug) : 
                Str::slug($request->name);

            // Vérifier l'unicité du slug
            $originalSlug = $data['slug'];
            $counter = 1;
            while (EventCategory::where('slug', $data['slug'])->exists()) {
                $data['slug'] = $originalSlug . '-' . $counter;
                $counter++;
            }

            EventCategory::create($data);

            return redirect()
                ->route('admin.categories.index')
                ->with('success', 'Catégorie créée avec succès !');

        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Erreur lors de la création : ' . $e->getMessage());
        }
    }

    /**
     * Affichage d'une catégorie
     */
    public function show(EventCategory $category)
    {
        $category->load(['events' => function($query) {
            $query->with(['promoter', 'ticketTypes'])
                  ->orderBy('event_date', 'desc');
        }]);

        // Statistiques de la catégorie
        $stats = [
            'total_events' => $category->events->count(),
            'published_events' => $category->events()->where('status', 'published')->count(),
            'upcoming_events' => $category->events()
                ->where('event_date', '>=', now())
                ->where('status', 'published')
                ->count(),
        ];

        return view('admin.categories.show', compact('category', 'stats'));
    }

    /**
     * Formulaire d'édition catégorie
     */
    public function edit(EventCategory $category)
    {
        return view('admin.categories.edit', compact('category'));
    }

    /**
     * Mise à jour catégorie
     */
    public function update(Request $request, EventCategory $category)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('event_categories')->ignore($category->id)],
            'description' => 'nullable|string|max:1000',
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('event_categories')->ignore($category->id)],
            'icon' => 'nullable|string|max:100',
        ]);

        try {
            $data = $request->only([
                'name', 'description', 'icon'
            ]);

            // Mettre à jour le slug si fourni
            if ($request->filled('slug')) {
                $slug = Str::slug($request->slug);
                
                // Vérifier l'unicité du slug (en excluant cette catégorie)
                $originalSlug = $slug;
                $counter = 1;
                while (EventCategory::where('slug', $slug)->where('id', '!=', $category->id)->exists()) {
                    $slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
                $data['slug'] = $slug;
            }

            $category->update($data);

            return redirect()
                ->route('admin.categories.index')
                ->with('success', 'Catégorie mise à jour avec succès !');

        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Erreur lors de la mise à jour : ' . $e->getMessage());
        }
    }

    /**
     * Suppression d'une catégorie
     */
    public function destroy(EventCategory $category)
    {
        // Vérifier si la catégorie a des événements
        if ($category->events()->exists()) {
            return back()->with('error', 'Impossible de supprimer une catégorie qui contient des événements.');
        }

        try {
            $category->delete();
            
            return redirect()
                ->route('admin.categories.index')
                ->with('success', 'Catégorie supprimée avec succès !');

        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la suppression : ' . $e->getMessage());
        }
    }
}