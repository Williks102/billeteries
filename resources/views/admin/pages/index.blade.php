@extends('layouts.admin')

@section('title', 'Gestion des pages')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">📄 Gestion des pages</h1>
            <p class="text-muted">Gérez le contenu des pages statiques de votre site</p>
        </div>
        <a href="{{ route('admin.pages.create') }}" class="btn btn-orange">
            <i class="fas fa-plus me-2"></i>Nouvelle page
        </a>
    </div>

    <!-- Filtres et recherche -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Recherche</label>
                    <input type="text" name="search" class="form-control" 
                           placeholder="Titre, slug ou contenu..." 
                           value="{{ request('search') }}">
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Statut</label>
                    <select name="status" class="form-select">
                        <option value="">Tous les statuts</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Actives</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactives</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Template</label>
                    <select name="template" class="form-select">
                        <option value="">Tous les templates</option>
                        @foreach(\App\Models\Page::getAvailableTemplates() as $key => $label)
                        <option value="{{ $key }}" {{ request('template') === $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-outline-orange w-100">
                        <i class="fas fa-search me-2"></i>Filtrer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste des pages -->
    <div class="card">
        <div class="card-body">
            @if($pages->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Titre</th>
                                <th>Slug</th>
                                <th>Template</th>
                                <th>Statut</th>
                                <th>Menu</th>
                                <th>Mise à jour</th>
                                <th width="200">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="sortable-pages">
                            @foreach($pages as $page)
                            <tr data-page-id="{{ $page->id }}">
                                <td>
                                    <div class="d-flex align-items-center">
                                        @if($page->show_in_menu)
                                            <i class="fas fa-grip-vertical text-muted me-2" style="cursor: move;" title="Glisser pour réorganiser"></i>
                                        @endif
                                        <div>
                                            <strong>{{ $page->title }}</strong>
                                            @if($page->excerpt)
                                                <br><small class="text-muted">{{ Str::limit($page->excerpt, 60) }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                
                                <td>
                                    <code>{{ $page->slug }}</code>
                                    <a href="{{ route('admin.pages.show', $page->slug) }}" 
                                       target="_blank" class="ms-2" title="Voir la page">
                                        <i class="fas fa-external-link-alt text-muted"></i>
                                    </a>
                                </td>
                                
                                <td>
                                    <span class="badge bg-secondary">{{ \App\Models\Page::getAvailableTemplates()[$page->template] ?? $page->template }}</span>
                                </td>
                                
                                <td>
                                    <form method="POST" action="{{ route('admin.pages.toggle-status', $page) }}" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm border-0 p-0">
                                            @if($page->is_active)
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check me-1"></i>Active
                                                </span>
                                            @else
                                                <span class="badge bg-secondary">
                                                    <i class="fas fa-times me-1"></i>Inactive
                                                </span>
                                            @endif
                                        </button>
                                    </form>
                                </td>
                                
                                <td>
                                    @if($page->show_in_menu)
                                        <span class="badge bg-primary">
                                            <i class="fas fa-bars me-1"></i>{{ $page->menu_order }}
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                
                                <td>
                                    <small class="text-muted">
                                        {{ $page->updated_at->format('d/m/Y H:i') }}
                                        <br>{{ $page->updated_at->diffForHumans() }}
                                    </small>
                                </td>
                                
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.pages.show', $page) }}" 
                                           class="btn btn-outline-info" title="Voir">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        
                                        <a href="{{ route('admin.pages.edit', $page) }}" 
                                           class="btn btn-outline-warning" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        
                                        <form method="POST" action="{{ route('admin.pages.duplicate', $page) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-secondary" title="Dupliquer">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </form>
                                        
                                        @if(!in_array($page->slug, ['about', 'terms', 'privacy', 'contact']))
                                        <form method="POST" action="{{ route('admin.pages.destroy', $page) }}" 
                                              class="d-inline" 
                                              onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette page ?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger" title="Supprimer">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                        @else
                                        <button class="btn btn-outline-danger" disabled title="Page protégée">
                                            <i class="fas fa-lock"></i>
                                        </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-4">
                    {{ $pages->withQueryString()->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                    <h5>Aucune page trouvée</h5>
                    <p class="text-muted">Aucune page ne correspond à vos critères de recherche.</p>
                    <a href="{{ route('admin.pages.create') }}" class="btn btn-orange">
                        <i class="fas fa-plus me-2"></i>Créer la première page
                    </a>
                </div>
            @endif
        </div>
    </div>

    <!-- Statistiques rapides -->
    <div class="row mt-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h4>{{ \App\Models\Page::count() }}</h4>
                    <small>Total pages</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h4>{{ \App\Models\Page::where('is_active', true)->count() }}</h4>
                    <small>Pages actives</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-orange text-white">
                <div class="card-body text-center">
                    <h4>{{ \App\Models\Page::where('show_in_menu', true)->count() }}</h4>
                    <small>Dans le menu</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h4>{{ \App\Models\Page::distinct('template')->count() }}</h4>
                    <small>Templates utilisés</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Script pour le tri des pages -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const sortable = Sortable.create(document.getElementById('sortable-pages'), {
        handle: '.fa-grip-vertical',
        animation: 150,
        onEnd: function(evt) {
            const pageIds = Array.from(document.querySelectorAll('#sortable-pages tr[data-page-id]'))
                               .map(tr => tr.getAttribute('data-page-id'));
            
            fetch('{{ route("admin.pages.reorder") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ pages: pageIds })
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    alert('Erreur lors de la réorganisation');
                    location.reload();
                }
            });
        }
    });
});
</script>
@endsection