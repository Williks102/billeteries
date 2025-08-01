<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PageController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
    }

    /**
     * Liste des pages
     */
    public function index(Request $request)
    {
        $query = Page::query();

        // Recherche
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        // Filtre par statut
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        // Filtre par template
        if ($request->filled('template')) {
            $query->where('template', $request->template);
        }

        $pages = $query->orderBy('menu_order', 'asc')
                      ->orderBy('title', 'asc')
                      ->paginate(20);

        return view('admin.pages.index', compact('pages'));
    }

    /**
     * Formulaire de création
     */
    public function create()
    {
        $templates = Page::getAvailableTemplates();
        return view('admin.pages.create', compact('templates'));
    }

    /**
     * Sauvegarde d'une nouvelle page
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:pages,slug',
            'excerpt' => 'nullable|string|max:500',
            'content' => 'required|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:255',
            'template' => 'required|string',
            'is_active' => 'boolean',
            'show_in_menu' => 'boolean',
            'menu_order' => 'nullable|integer|min:0',
        ]);

        try {
            $data = $request->all();
            
            // Générer le slug si vide
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['title']);
            } else {
                $data['slug'] = Str::slug($data['slug']);
            }

            // Valeurs par défaut
            $data['is_active'] = $request->has('is_active');
            $data['show_in_menu'] = $request->has('show_in_menu');
            $data['menu_order'] = $data['menu_order'] ?? 0;

            $page = Page::create($data);

            return redirect()->route('admin.pages.index')
                           ->with('success', "Page '{$page->title}' créée avec succès !");

        } catch (\Exception $e) {
            \Log::error('Erreur création page: ' . $e->getMessage());
            
            return redirect()->back()
                           ->with('error', 'Erreur lors de la création de la page')
                           ->withInput();
        }
    }

    /**
     * Affichage d'une page
     */
    public function show(Page $page)
    {
        return view('admin.pages.show', compact('page'));
    }

    /**
     * Formulaire d'édition
     */
    public function edit(Page $page)
    {
        $templates = Page::getAvailableTemplates();
        return view('admin.pages.edit', compact('page', 'templates'));
    }

    /**
     * Mise à jour d'une page
     */
    public function update(Request $request, Page $page)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:pages,slug,' . $page->id,
            'excerpt' => 'nullable|string|max:500',
            'content' => 'required|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:255',
            'template' => 'required|string',
            'is_active' => 'boolean',
            'show_in_menu' => 'boolean',
            'menu_order' => 'nullable|integer|min:0',
        ]);

        try {
            $data = $request->all();
            
            // Mise à jour du slug si modifié
            if (!empty($data['slug'])) {
                $data['slug'] = Str::slug($data['slug']);
            }

            // Valeurs par défaut
            $data['is_active'] = $request->has('is_active');
            $data['show_in_menu'] = $request->has('show_in_menu');
            $data['menu_order'] = $data['menu_order'] ?? 0;

            $page->update($data);

            return redirect()->route('admin.pages.index')
                           ->with('success', "Page '{$page->title}' mise à jour avec succès !");

        } catch (\Exception $e) {
            \Log::error('Erreur mise à jour page: ' . $e->getMessage());
            
            return redirect()->back()
                           ->with('error', 'Erreur lors de la mise à jour de la page')
                           ->withInput();
        }
    }

    /**
     * Suppression d'une page
     */
    public function destroy(Page $page)
    {
        try {
            // Empêcher la suppression de pages critiques
            $protectedSlugs = ['about', 'terms', 'privacy', 'contact'];
            
            if (in_array($page->slug, $protectedSlugs)) {
                return redirect()->back()
                               ->with('warning', 'Cette page ne peut pas être supprimée car elle est essentielle au fonctionnement du site.');
            }

            $pageTitle = $page->title;
            $page->delete();

            return redirect()->route('admin.pages.index')
                           ->with('success', "Page '{$pageTitle}' supprimée avec succès !");

        } catch (\Exception $e) {
            \Log::error('Erreur suppression page: ' . $e->getMessage());
            
            return redirect()->back()
                           ->with('error', 'Erreur lors de la suppression de la page');
        }
    }

    /**
     * Dupliquer une page
     */
    public function duplicate(Page $page)
    {
        try {
            $newPage = $page->replicate();
            $newPage->title = $page->title . ' (Copie)';
            $newPage->slug = $page->slug . '-copy';
            $newPage->is_active = false;
            $newPage->show_in_menu = false;
            $newPage->created_at = now();
            $newPage->updated_at = now();
            $newPage->save();

            return redirect()->route('admin.pages.edit', $newPage)
                           ->with('success', "Page dupliquée avec succès ! N'oubliez pas de modifier le titre et le slug.");

        } catch (\Exception $e) {
            \Log::error('Erreur duplication page: ' . $e->getMessage());
            
            return redirect()->back()
                           ->with('error', 'Erreur lors de la duplication de la page');
        }
    }

    /**
     * Basculer le statut actif/inactif
     */
    public function toggleStatus(Page $page)
    {
        try {
            $page->update(['is_active' => !$page->is_active]);
            
            $status = $page->is_active ? 'activée' : 'désactivée';
            
            return redirect()->back()
                           ->with('success', "Page '{$page->title}' {$status} avec succès !");

        } catch (\Exception $e) {
            \Log::error('Erreur changement statut page: ' . $e->getMessage());
            
            return redirect()->back()
                           ->with('error', 'Erreur lors du changement de statut');
        }
    }

    /**
     * Réorganiser les pages du menu
     */
    public function reorder(Request $request)
    {
        $request->validate([
            'pages' => 'required|array',
            'pages.*' => 'exists:pages,id'
        ]);

        try {
            foreach ($request->pages as $order => $pageId) {
                Page::where('id', $pageId)->update(['menu_order' => $order + 1]);
            }

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            \Log::error('Erreur réorganisation pages: ' . $e->getMessage());
            
            return response()->json(['success' => false], 500);
        }
    }
}