@props(['action', 'name' => 'q', 'value' => '', 'label' => 'Rechercher un événement'])
<form method="GET" action="{{ $action }}" role="search" class="mb-4">
    <label for="{{ $name }}-input" class="form-label fw-semibold">{{ $label }}</label>
    <div class="input-group">
        <input type="search" id="{{ $name }}-input" name="{{ $name }}" value="{{ $value }}" class="form-control"
               style="font-size:16px;min-height:44px;" minlength="2" maxlength="100" placeholder="Titre, lieu, catégorie…" autocomplete="off">
        <button type="submit" class="btn btn-primary" style="min-height:44px;min-width:44px;">Rechercher</button>
    </div>
</form>
