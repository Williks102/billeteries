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

        if ($request->filled('status')) {
            $isActive = $request->status === 'active';
            $query->where('is_active', $isActive);
        }

        // Tri par ordre d'affichage puis nom
        $categories = $query->orderBy('display_order')->orderBy('name')->paginate(20);

        // Statistiques
        $stats = [
            'total' => EventCategory::count(),
            'active' => EventCategory::where('is_active', true)->count(),
            'inactive' => EventCategory::where('is_active', false)->count(),
            'with_events' => EventCategory::has('events')->count(),
            'empty_categories' => EventCategory::doesntHave('events')->count(),
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
            'color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'is_active' => 'boolean',
            'display_order' => 'nullable|integer|min:0',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:300',
        ]);

        try {
            $data = $request->only([
                'name', 'description', 'icon', 'color', 'is_active', 
                'display_order', 'meta_title', 'meta_description'
            ]);

            // Générer le slug s'il n'est pas fourni
            $data['slug'] = $request->slug ?: Str::slug($request->name);

            // Vérifier l'unicité du slug généré
            $originalSlug = $data['slug'];
            $counter = 1;
            while (EventCategory::where('slug', $data['slug'])->exists()) {
                $data['slug'] = $originalSlug . '-' . $counter;
                $counter++;
            }

            // Gestion de l'image
            if ($request->hasFile('image')) {
                $data['image'] = $request->file('image')->store('categories', 'public');
            }

            // Définir l'ordre d'affichage par défaut
            if (empty($data['display_order'])) {
                $data['display_order'] = (EventCategory::max('display_order') ?? 0) + 1;
            }

            $category = EventCategory::create($data);

            \Log::info('Catégorie créée par admin', [
                'admin_id' => auth()->id(),
                'category_id' => $category->id,
                'category_name' => $category->name
            ]);

            return redirect()->route('admin.categories.index')
                ->with('success', "Catégorie '{$category->name}' créée avec succès !");

        } catch (\Exception $e) {
            \Log::error('Erreur création catégorie: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Erreur lors de la création de la catégorie')
                ->withInput();
        }
    }

    /**
     * Affichage détail catégorie
     */
    public function show(EventCategory $category)
    {
        $category->load(['events' => function($query) {
            $query->latest()->take(10);
        }]);

        // Statistiques de la catégorie
        $stats = [
            'events_count' => $category->events()->count(),
            'published_events' => $category->events()->where('status', 'published')->count(),
            'pending_events' => $category->events()->where('status', 'pending')->count(),
            'total_tickets_sold' => $category->events()
                ->join('ticket_types', 'events.id', '=', 'ticket_types.event_id')
                ->sum('ticket_types.quantity_sold'),
            'total_revenue' => $category->events()
                ->join('orders', 'events.id', '=', 'orders.event_id')
                ->where('orders.payment_status', 'paid')
                ->sum('orders.total_amount'),
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
            'color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'is_active' => 'boolean',
            'display_order' => 'nullable|integer|min:0',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:300',
        ]);

        try {
            $data = $request->only([
                'name', 'description', 'icon', 'color', 'is_active', 
                'display_order', 'meta_title', 'meta_description'
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
            } elseif ($request->name !== $category->name) {
                // Régénérer le slug si le nom a changé
                $data['slug'] = Str::slug($request->name);
            }

            // Gestion de l'image
            if ($request->hasFile('image')) {
                // Supprimer l'ancienne image
                if ($category->image) {
                    Storage::disk('public')->delete($category->image);
                }
                $data['image'] = $request->file('image')->store('categories', 'public');
            }

            $category->update($data);

            \Log::info('Catégorie modifiée par admin', [
                'admin_id' => auth()->id(),
                'category_id' => $category->id,
                'category_name' => $category->name
            ]);

            return redirect()->route('admin.categories.index')
                ->with('success', "Catégorie '{$category->name}' mise à jour avec succès !");

        } catch (\Exception $e) {
            \Log::error('Erreur mise à jour catégorie: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Erreur lors de la mise à jour')
                ->withInput();
        }
    }

    /**
     * Suppression catégorie
     */
    public function destroy(EventCategory $category)
    {
        try {
            // Vérifier qu'il n'y a pas d'événements associés
            $eventsCount = $category->events()->count();
            
            if ($eventsCount > 0) {
                return redirect()->back()
                    ->with('error', "Impossible de supprimer une catégorie qui contient {$eventsCount} événement(s). Déplacez d'abord les événements vers une autre catégorie.");
            }

            // Supprimer l'image si elle existe
            if ($category->image) {
                Storage::disk('public')->delete($category->image);
            }

            $categoryName = $category->name;
            $category->delete();

            \Log::info('Catégorie supprimée par admin', [
                'admin_id' => auth()->id(),
                'category_name' => $categoryName
            ]);

            return redirect()->route('admin.categories.index')
                ->with('success', "Catégorie '{$categoryName}' supprimée avec succès !");

        } catch (\Exception $e) {
            \Log::error('Erreur suppression catégorie: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Erreur lors de la suppression de la catégorie');
        }
    }

    /**
     * Basculer le statut actif/inactif
     */
    public function toggleStatus(EventCategory $category)
    {
        try {
            $category->update(['is_active' => !$category->is_active]);
            
            $status = $category->is_active ? 'activée' : 'désactivée';
            
            \Log::info('Statut catégorie modifié par admin', [
                'admin_id' => auth()->id(),
                'category_id' => $category->id,
                'new_status' => $category->is_active
            ]);

            return redirect()->back()
                ->with('success', "Catégorie '{$category->name}' {$status} avec succès !");

        } catch (\Exception $e) {
            \Log::error('Erreur changement statut catégorie: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Erreur lors du changement de statut');
        }
    }

    /**
     * Réorganiser les catégories (ordre d'affichage)
     */
    public function reorder(Request $request)
    {
        $request->validate([
            'categories' => 'required|array',
            'categories.*' => 'exists:event_categories,id'
        ]);

        try {
            foreach ($request->categories as $order => $categoryId) {
                EventCategory::where('id', $categoryId)->update(['display_order' => $order + 1]);
            }

            \Log::info('Ordre catégories modifié par admin', [
                'admin_id' => auth()->id(),
                'categories_reordered' => count($request->categories)
            ]);

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            \Log::error('Erreur réorganisation catégories: ' . $e->getMessage());
            
            return response()->json(['success' => false], 500);
        }
    }

    /**
     * Dupliquer une catégorie
     */
    public function duplicate(EventCategory $category)
    {
        try {
            $newCategory = $category->replicate();
            $newCategory->name = $category->name . ' (Copie)';
            $newCategory->slug = $category->slug . '-copy';
            $newCategory->is_active = false;
            $newCategory->display_order = (EventCategory::max('display_order') ?? 0) + 1;
            $newCategory->created_at = now();
            $newCategory->updated_at = now();

            // Vérifier l'unicité du slug
            $originalSlug = $newCategory->slug;
            $counter = 1;
            while (EventCategory::where('slug', $newCategory->slug)->exists()) {
                $newCategory->slug = $originalSlug . '-' . $counter;
                $counter++;
            }

            // Copier l'image si elle existe
            if ($category->image) {
                $originalPath = $category->image;
                $extension = pathinfo($originalPath, PATHINFO_EXTENSION);
                $newPath = 'categories/' . Str::random(40) . '.' . $extension;
                
                if (Storage::disk('public')->exists($originalPath)) {
                    Storage::disk('public')->copy($originalPath, $newPath);
                    $newCategory->image = $newPath;
                }
            }

            $newCategory->save();

            \Log::info('Catégorie dupliquée par admin', [
                'admin_id' => auth()->id(),
                'original_category_id' => $category->id,
                'new_category_id' => $newCategory->id
            ]);

            return redirect()->route('admin.categories.edit', $newCategory)
                ->with('success', "Catégorie dupliquée avec succès ! N'oubliez pas de modifier le nom et les paramètres.");

        } catch (\Exception $e) {
            \Log::error('Erreur duplication catégorie: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Erreur lors de la duplication de la catégorie');
        }
    }

    /**
     * Fusionner deux catégories
     */
    public function merge(Request $request, EventCategory $category)
    {
        $request->validate([
            'target_category_id' => 'required|exists:event_categories,id|different:' . $category->id,
        ]);

        try {
            $targetCategory = EventCategory::findOrFail($request->target_category_id);
            
            // Déplacer tous les événements vers la catégorie cible
            $eventsCount = $category->events()->count();
            $category->events()->update(['category_id' => $targetCategory->id]);

            // Supprimer l'image de l'ancienne catégorie
            if ($category->image) {
                Storage::disk('public')->delete($category->image);
            }

            $categoryName = $category->name;
            $category->delete();

            \Log::info('Catégories fusionnées par admin', [
                'admin_id' => auth()->id(),
                'source_category' => $categoryName,
                'target_category_id' => $targetCategory->id,
                'events_moved' => $eventsCount
            ]);

            return redirect()->route('admin.categories.index')
                ->with('success', "Catégorie '{$categoryName}' fusionnée avec '{$targetCategory->name}'. {$eventsCount} événement(s) déplacé(s).");

        } catch (\Exception $e) {
            \Log::error('Erreur fusion catégories: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Erreur lors de la fusion des catégories');
        }
    }

    /**
     * Export CSV des catégories
     */
    public function export(Request $request)
    {
        try {
            $query = EventCategory::withCount('events');

            if ($request->filled('status')) {
                $isActive = $request->status === 'active';
                $query->where('is_active', $isActive);
            }

            $categories = $query->orderBy('display_order')->orderBy('name')->get();

            $csvContent = "ID,Nom,Slug,Description,Statut,Ordre,Événements,Créé le\n";
            
            foreach ($categories as $category) {
                $csvContent .= implode(',', [
                    $category->id,
                    '"' . addslashes($category->name) . '"',
                    $category->slug,
                    '"' . addslashes($category->description ?? '') . '"',
                    $category->is_active ? 'Actif' : 'Inactif',
                    $category->display_order,
                    $category->events_count,
                    $category->created_at->format('Y-m-d H:i:s')
                ]) . "\n";
            }

            return response($csvContent)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="categories-export-' . now()->format('Y-m-d') . '.csv"');

        } catch (\Exception $e) {
            \Log::error('Erreur export catégories: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Erreur lors de l\'export');
        }
    }
}