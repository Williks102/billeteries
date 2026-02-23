@extends('layouts.admin')

@section('title', 'Modifier la page')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">📝 Modifier la page</h1>
            <p class="text-muted">Modifiez le contenu de : <strong>{{ $page->title }}</strong></p>
        </div>
        <div>
            <a href="{{ route('admin.pages.show', $page->slug) }}" target="_blank" class="btn btn-outline-info me-2">
                <i class="fas fa-external-link-alt me-2"></i>Voir la page
            </a>
            <a href="{{ route('admin.pages.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Retour à la liste
            </a>
        </div>
    </div>

    <!-- Alertes -->
    @if(in_array($page->slug, ['about', 'terms', 'privacy', 'contact']))
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>Page protégée :</strong> Cette page est essentielle au fonctionnement du site. Modifiez-la avec précaution.
    </div>
    @endif

    <!-- Formulaire -->
    <form action="{{ route('admin.pages.update', $page) }}" method="POST" id="pageForm">
        @csrf
        @method('PUT')
        
        <div class="row">
            <!-- Contenu principal -->
            <div class="col-lg-8">
                <!-- Informations de base -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Contenu de la page</h5>
                        <small class="text-muted">
                            Créée le {{ $page->created_at->format('d/m/Y à H:i') }}
                            @if($page->updated_at != $page->created_at)
                                • Modifiée le {{ $page->updated_at->format('d/m/Y à H:i') }}
                            @endif
                        </small>
                    </div>
                    <div class="card-body">
                        <!-- Titre -->
                        <div class="mb-3">
                            <label for="title" class="form-label">Titre de la page *</label>
                            <input type="text" 
                                   class="form-control @error('title') is-invalid @enderror" 
                                   id="title" 
                                   name="title" 
                                   value="{{ old('title', $page->title) }}" 
                                   required 
                                   placeholder="Ex: À propos de nous">
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Ce titre apparaîtra en haut de la page</small>
                        </div>

                        <!-- Slug -->
                        <div class="mb-3">
                            <label for="slug" class="form-label">URL de la page (slug)</label>
                            <div class="input-group">
                                <span class="input-group-text">{{ url('/') }}/</span>
                                <input type="text" 
                                       class="form-control @error('slug') is-invalid @enderror" 
                                       id="slug" 
                                       name="slug" 
                                       value="{{ old('slug', $page->slug) }}" 
                                       placeholder="about-us"
                                       {{ in_array($page->slug, ['about', 'terms', 'privacy', 'contact']) ? 'readonly' : '' }}>
                                @error('slug')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            @if(in_array($page->slug, ['about', 'terms', 'privacy', 'contact']))
                                <small class="text-warning">Le slug de cette page protégée ne peut pas être modifié.</small>
                            @else
                                <small class="text-muted">Utilisez uniquement des lettres, chiffres et tirets.</small>
                            @endif
                        </div>

                        <!-- Excerpt -->
                        <div class="mb-3">
                            <label for="excerpt" class="form-label">Description courte</label>
                            <textarea class="form-control @error('excerpt') is-invalid @enderror" 
                                      id="excerpt" 
                                      name="excerpt" 
                                      rows="2" 
                                      placeholder="Courte description de la page pour les moteurs de recherche...">{{ old('excerpt', $page->excerpt) }}</textarea>
                            @error('excerpt')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Maximum 500 caractères. Utilisé pour le SEO.</small>
                        </div>

                        <!-- Contenu -->
                        <div class="mb-3">
                            <label for="content" class="form-label">Contenu principal *</label>
                            <textarea class="form-control @error('content') is-invalid @enderror" 
                                      id="content" 
                                      name="content" 
                                      rows="15" 
                                      required 
                                      placeholder="Rédigez le contenu de votre page ici...">{{ old('content', $page->content) }}</textarea>
                            @error('content')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Vous pouvez utiliser du HTML pour la mise en forme.</small>
                        </div>

                        <!-- Aperçu en temps réel -->
                        <div class="mb-3">
                            <button type="button" class="btn btn-outline-info" data-bs-toggle="collapse" data-bs-target="#preview">
                                <i class="fas fa-eye me-2"></i>Aperçu en temps réel
                            </button>
                            <div class="collapse mt-3" id="preview">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <div id="preview-content">
                                            {!! $page->content !!}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Actions rapides -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-cog me-2"></i>Actions</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" name="action" value="save" class="btn btn-orange">
                                <i class="fas fa-save me-2"></i>Mettre à jour
                            </button>
                            <button type="submit" name="action" value="save_and_continue" class="btn btn-outline-orange">
                                <i class="fas fa-save me-2"></i>Mettre à jour et continuer
                            </button>
                            
                            @if(!in_array($page->slug, ['about', 'terms', 'privacy', 'contact']))
                            <form action="{{ route('admin.pages.duplicate', $page) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-outline-secondary w-100">
                                    <i class="fas fa-copy me-2"></i>Dupliquer cette page
                                </button>
                            </form>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Statistiques -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Statistiques</h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="border-end">
                                    <h4 class="text-orange mb-0">{{ str_word_count(strip_tags($page->content)) }}</h4>
                                    <small class="text-muted">Mots</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <h4 class="text-orange mb-0">{{ strlen(strip_tags($page->content)) }}</h4>
                                <small class="text-muted">Caractères</small>
                            </div>
                        </div>
                        <hr>
                        <div class="small text-muted">
                            <div class="d-flex justify-content-between">
                                <span>Statut :</span>
                                <span class="badge {{ $page->is_active ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $page->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                            <div class="d-flex justify-content-between mt-2">
                                <span>Template :</span>
                                <span>{{ $templates[$page->template] ?? $page->template }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Paramètres -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-sliders-h me-2"></i>Paramètres</h6>
                    </div>
                    <div class="card-body">
                        <!-- Template -->
                        <div class="mb-3">
                            <label for="template" class="form-label">Template</label>
                            <select class="form-select @error('template') is-invalid @enderror" 
                                    id="template" 
                                    name="template" 
                                    required>
                                @foreach($templates as $templateKey => $templateName)
                                <option value="{{ $templateKey }}" {{ old('template', $page->template) === $templateKey ? 'selected' : '' }}>
                                    {{ $templateName }}
                                </option>
                                @endforeach
                            </select>
                            @error('template')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Choisissez la mise en page de votre page</small>
                        </div>

                        <!-- Statut -->
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="is_active" 
                                       name="is_active" 
                                       {{ old('is_active', $page->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    Page active
                                </label>
                            </div>
                            <small class="text-muted">Une page inactive ne sera pas accessible au public</small>
                        </div>

                        <!-- Menu -->
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="show_in_menu" 
                                       name="show_in_menu" 
                                       {{ old('show_in_menu', $page->show_in_menu) ? 'checked' : '' }}>
                                <label class="form-check-label" for="show_in_menu">
                                    Afficher dans le menu
                                </label>
                            </div>
                            <small class="text-muted">La page apparaîtra dans le menu principal</small>
                        </div>

                        <!-- Ordre menu -->
                        <div class="mb-3" id="menu_order_group" style="{{ old('show_in_menu', $page->show_in_menu) ? '' : 'display: none;' }}">
                            <label for="menu_order" class="form-label">Ordre dans le menu</label>
                            <input type="number" 
                                   class="form-control @error('menu_order') is-invalid @enderror" 
                                   id="menu_order" 
                                   name="menu_order" 
                                   value="{{ old('menu_order', $page->menu_order) }}" 
                                   min="0">
                            @error('menu_order')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Plus le nombre est petit, plus la page apparaît en premier</small>
                        </div>
                    </div>
                </div>

                <!-- SEO -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-search me-2"></i>Référencement (SEO)</h6>
                    </div>
                    <div class="card-body">
                        <!-- Meta titre -->
                        <div class="mb-3">
                            <label for="meta_title" class="form-label">Titre SEO</label>
                            <input type="text" 
                                   class="form-control @error('meta_title') is-invalid @enderror" 
                                   id="meta_title" 
                                   name="meta_title" 
                                   value="{{ old('meta_title', $page->meta_title) }}" 
                                   maxlength="60"
                                   placeholder="Titre optimisé pour Google">
                            @error('meta_title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Maximum 60 caractères. Laissez vide pour utiliser le titre de la page.</small>
                        </div>

                        <!-- Meta description -->
                        <div class="mb-3">
                            <label for="meta_description" class="form-label">Description SEO</label>
                            <textarea class="form-control @error('meta_description') is-invalid @enderror" 
                                      id="meta_description" 
                                      name="meta_description" 
                                      rows="3" 
                                      maxlength="160"
                                      placeholder="Description qui apparaîtra dans les résultats Google">{{ old('meta_description', $page->meta_description) }}</textarea>
                            @error('meta_description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Maximum 160 caractères. Laissez vide pour génération automatique.</small>
                        </div>

                        <!-- Aperçu Google -->
                        <div class="mt-3">
                            <label class="form-label">Aperçu Google</label>
                            <div class="border p-3 bg-light rounded">
                                <div class="google-preview">
                                    <div class="google-title text-primary" style="font-size: 18px; line-height: 1.2;">
                                        <span id="google-title-preview">{{ $page->seo_title }}</span>
                                    </div>
                                    <div class="google-url text-success" style="font-size: 14px;">
                                        <span>{{ url('/') }}/</span><span id="google-url-preview">{{ $page->slug }}</span>
                                    </div>
                                    <div class="google-description" style="font-size: 13px; color: #545454; line-height: 1.4;">
                                        <span id="google-description-preview">{{ $page->seo_description }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Historique rapide -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-history me-2"></i>Historique</h6>
                    </div>
                    <div class="card-body">
                        <div class="small">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Créée :</span>
                                <span>{{ $page->created_at->format('d/m/Y H:i') }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Modifiée :</span>
                                <span>{{ $page->updated_at->format('d/m/Y H:i') }}</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>URL actuelle :</span>
                                <a href="{{ route('admin.pages.show', $page->slug) }}" target="_blank" class="small">
                                    Voir <i class="fas fa-external-link-alt"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script src="{{ asset('js/admin-pages-edit.js') }}" defer></script>
@endsection