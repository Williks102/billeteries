@props(['action', 'category' => null])
{{-- Formulaire partagé création / édition de catégorie. --}}
<form action="{{ $action }}" method="POST" novalidate>
    @csrf
    @if($category)
        @method('PUT')
    @endif

    @if($errors->any())
        <div class="alert alert-danger" role="alert" id="form-errors">
            Le formulaire contient {{ $errors->count() }} erreur(s). Corrigez les champs signalés.
        </div>
    @endif

    <div class="mb-3">
        <label for="name" class="form-label">Nom <span class="text-danger" aria-hidden="true">*</span></label>
        <input type="text" id="name" name="name" required maxlength="255"
               class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $category?->name) }}"
               @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
        @error('name') <div class="invalid-feedback" id="name-error">{{ $message }}</div> @enderror
    </div>

    <div class="mb-3">
        <label for="slug" class="form-label">Identifiant d'URL (slug)</label>
        <input type="text" id="slug" name="slug" maxlength="255"
               class="form-control @error('slug') is-invalid @enderror"
               value="{{ old('slug', $category?->slug) }}" aria-describedby="slug-help">
        <div class="form-text" id="slug-help">Laissez vide pour le générer à partir du nom.</div>
        @error('slug') <div class="invalid-feedback d-block" role="alert">{{ $message }}</div> @enderror
    </div>

    <div class="mb-3">
        <label for="icon" class="form-label">Icône (classe Font Awesome)</label>
        <input type="text" id="icon" name="icon" maxlength="100" placeholder="fas fa-music"
               class="form-control @error('icon') is-invalid @enderror"
               value="{{ old('icon', $category?->icon) }}">
        @error('icon') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="mb-4">
        <label for="description" class="form-label">Description</label>
        <textarea id="description" name="description" rows="4" maxlength="1000"
                  class="form-control @error('description') is-invalid @enderror">{{ old('description', $category?->description) }}</textarea>
        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="d-flex flex-column flex-sm-row gap-2">
        <button type="submit" class="btn btn-primary">{{ $category ? 'Enregistrer' : 'Créer la catégorie' }}</button>
        <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary">Annuler</a>
    </div>
</form>
