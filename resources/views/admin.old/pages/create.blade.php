@extends('layouts.admin')

@section('title', 'Créer une page')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">📄 Créer une nouvelle page</h1>
            <p class="text-muted">Ajoutez du contenu statique à votre site</p>
        </div>
        <a href="{{ route('admin.pages.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Retour à la liste
        </a>
    </div>

    <!-- Formulaire -->
    <form action="{{ route('admin.pages.store') }}" method="POST" id="pageForm">
        @csrf
        
        <div class="row">
            <!-- Contenu principal -->
            <div class="col-lg-8">
                <!-- Informations de base -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Contenu de la page</h5>
                    </div>
                    <div class="card-body">
                        <!-- Titre -->
                        <div class="mb-3">
                            <label for="title" class="form-label">Titre de la page *</label>
                            <input type="text" 
                                   class="form-control @error('title') is-invalid @enderror" 
                                   id="title" 
                                   name="title" 
                                   value="{{ old('title') }}" 
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
                                       value="{{ old('slug') }}" 
                                       placeholder="about-us">
                                @error('slug')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <small class="text-muted">Laissez vide pour génération automatique. Utilisez uniquement des lettres, chiffres et tirets.</small>
                        </div>

                        <!-- Excerpt -->
                        <div class="mb-3">
                            <label for="excerpt" class="form-label">Description courte</label>
                            <textarea class="form-control @error('excerpt') is-invalid @enderror" 
                                      id="excerpt" 
                                      name="excerpt" 
                                      rows="2" 
                                      placeholder="Courte description de la page pour les moteurs de recherche...">{{ old('excerpt') }}</textarea>
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
                                      placeholder="Rédigez le contenu de votre page ici...">{{ old('content') }}</textarea>
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
                                            <p class="text-muted">Tapez du contenu pour voir l'aperçu...</p>
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
                                <i class="fas fa-save me-2"></i>Sauvegarder
                            </button>
                            <button type="submit" name="action" value="save_and_continue" class="btn btn-outline-orange">
                                <i class="fas fa-save me-2"></i>Sauvegarder et continuer
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="resetForm()">
                                <i class="fas fa-undo me-2"></i>Réinitialiser
                            </button>
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
                                <option value="{{ $templateKey }}" {{ old('template', 'default') === $templateKey ? 'selected' : '' }}>
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
                                       {{ old('is_active', true) ? 'checked' : '' }}>
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
                                       {{ old('show_in_menu') ? 'checked' : '' }}>
                                <label class="form-check-label" for="show_in_menu">
                                    Afficher dans le menu
                                </label>
                            </div>
                            <small class="text-muted">La page apparaîtra dans le menu principal</small>
                        </div>

                        <!-- Ordre menu -->
                        <div class="mb-3" id="menu_order_group" style="{{ old('show_in_menu') ? '' : 'display: none;' }}">
                            <label for="menu_order" class="form-label">Ordre dans le menu</label>
                            <input type="number" 
                                   class="form-control @error('menu_order') is-invalid @enderror" 
                                   id="menu_order" 
                                   name="menu_order" 
                                   value="{{ old('menu_order', 0) }}" 
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
                                   value="{{ old('meta_title') }}" 
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
                                      placeholder="Description qui apparaîtra dans les résultats Google">{{ old('meta_description') }}</textarea>
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
                                        <span id="google-title-preview">Titre de votre page</span>
                                    </div>
                                    <div class="google-url text-success" style="font-size: 14px;">
                                        <span>{{ url('/') }}/</span><span id="google-url-preview">page</span>
                                    </div>
                                    <div class="google-description" style="font-size: 13px; color: #545454; line-height: 1.4;">
                                        <span id="google-description-preview">Description de votre page qui apparaîtra dans les résultats de recherche Google.</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Aide -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Aide</h6>
                    </div>
                    <div class="card-body">
                        <div class="small">
                            <p><strong>HTML autorisé :</strong></p>
                            <ul class="mb-2">
                                <li><code>&lt;h1&gt; à &lt;h6&gt;</code> : Titres</li>
                                <li><code>&lt;p&gt;</code> : Paragraphes</li>
                                <li><code>&lt;strong&gt;, &lt;em&gt;</code> : Mise en forme</li>
                                <li><code>&lt;ul&gt;, &lt;ol&gt;, &lt;li&gt;</code> : Listes</li>
                                <li><code>&lt;a href=""&gt;</code> : Liens</li>
                                <li><code>&lt;img src=""&gt;</code> : Images</li>
                            </ul>
                            <p><strong>Conseil :</strong> Utilisez l'aperçu pour vérifier le rendu de votre contenu.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Génération automatique du slug
    const titleInput = document.getElementById('title');
    const slugInput = document.getElementById('slug');
    
    titleInput.addEventListener('input', function() {
        if (!slugInput.dataset.manual) {
            const slug = this.value
                .toLowerCase()
                .replace(/[^\w ]+/g, '')
                .replace(/ +/g, '-')
                .substring(0, 50);
            slugInput.value = slug;
            
            // Mettre à jour l'aperçu Google
            updateGooglePreview();
        }
    });
    
    slugInput.addEventListener('input', function() {
        this.dataset.manual = 'true';
        updateGooglePreview();
    });
    
    // Affichage conditionnel de l'ordre du menu
    const showInMenuCheckbox = document.getElementById('show_in_menu');
    const menuOrderGroup = document.getElementById('menu_order_group');
    
    showInMenuCheckbox.addEventListener('change', function() {
        menuOrderGroup.style.display = this.checked ? 'block' : 'none';
    });
    
    // Aperçu en temps réel du contenu
    const contentTextarea = document.getElementById('content');
    const previewContent = document.getElementById('preview-content');
    
    contentTextarea.addEventListener('input', function() {
        previewContent.innerHTML = this.value || '<p class="text-muted">Tapez du contenu pour voir l\'aperçu...</p>';
    });
    
    // Mise à jour de l'aperçu Google
    function updateGooglePreview() {
        const title = document.getElementById('meta_title').value || titleInput.value || 'Titre de votre page';
        const slug = slugInput.value || 'page';
        const description = document.getElementById('meta_description').value || 
                          document.getElementById('excerpt').value || 
                          'Description de votre page qui apparaîtra dans les résultats de recherche Google.';
        
        document.getElementById('google-title-preview').textContent = title.substring(0, 60);
        document.getElementById('google-url-preview').textContent = slug;
        document.getElementById('google-description-preview').textContent = description.substring(0, 160);
    }
    
    // Écouter les changements des champs SEO
    document.getElementById('meta_title').addEventListener('input', updateGooglePreview);
    document.getElementById('meta_description').addEventListener('input', updateGooglePreview);
    document.getElementById('excerpt').addEventListener('input', updateGooglePreview);
    
    // Compteur de caractères
    function addCharCounter(elementId, maxLength) {
        const element = document.getElementById(elementId);
        const counter = document.createElement('small');
        counter.className = 'text-muted float-end';
        element.parentNode.appendChild(counter);
        
        function updateCounter() {
            const remaining = maxLength - element.value.length;
            counter.textContent = `${element.value.length}/${maxLength}`;
            counter.className = remaining < 10 ? 'text-danger small float-end' : 'text-muted small float-end';
        }
        
        element.addEventListener('input', updateCounter);
        updateCounter();
    }
    
    // Ajouter les compteurs
    addCharCounter('meta_title', 60);
    addCharCounter('meta_description', 160);
    addCharCounter('excerpt', 500);
    
    // Initialiser l'aperçu Google
    updateGooglePreview();
});

// Fonction de réinitialisation du formulaire
function resetForm() {
    if (confirm('Êtes-vous sûr de vouloir réinitialiser le formulaire ? Toutes les données non sauvegardées seront perdues.')) {
        document.getElementById('pageForm').reset();
        document.getElementById('preview-content').innerHTML = '<p class="text-muted">Tapez du contenu pour voir l\'aperçu...</p>';
        document.getElementById('menu_order_group').style.display = 'none';
        
        // Réinitialiser les données de slug manuel
        document.getElementById('slug').removeAttribute('data-manual');
    }
}

// Validation avant soumission
document.getElementById('pageForm').addEventListener('submit', function(e) {
    const title = document.getElementById('title').value.trim();
    const content = document.getElementById('content').value.trim();
    
    if (!title) {
        alert('Le titre de la page est obligatoire.');
        e.preventDefault();
        document.getElementById('title').focus();
        return false;
    }
    
    if (!content) {
        alert('Le contenu de la page est obligatoire.');
        e.preventDefault();
        document.getElementById('content').focus();
        return false;
    }
});
</script>
@endsection