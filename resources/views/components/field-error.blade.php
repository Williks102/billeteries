@props(['name'])
{{-- Message d'erreur de validation sous un champ. Le champ porte aria-describedby="<id> ... {{ $name }}-error" et aria-invalid. --}}
@error($name)
    <div id="{{ $name }}-error" class="invalid-feedback d-block" role="alert"><i class="fas fa-exclamation-circle me-1" aria-hidden="true"></i>{{ $message }}</div>
@enderror
