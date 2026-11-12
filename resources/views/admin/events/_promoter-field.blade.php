{{-- Choix du promoteur (admin) : inclus en tête de events/_form via `topFields`. Reçoit $promoteurs et $errors. --}}
<section class="ev-card" aria-labelledby="ev-promoter">
    <h2 id="ev-promoter" class="ev-title">Promoteur</h2>
    <label class="form-label" for="promoter_id">Créer cet événement pour <span class="text-danger" aria-hidden="true">*</span></label>
    <select id="promoter_id" name="promoter_id" required
            class="form-select @error('promoter_id') is-invalid @enderror"
            @error('promoter_id') aria-invalid="true" aria-describedby="promoter_id-error" @enderror>
        <option value="">Choisir un promoteur</option>
        @foreach ($promoteurs as $promoteur)
            <option value="{{ $promoteur->id }}" @selected((string) old('promoter_id') === (string) $promoteur->id)>
                {{ $promoteur->name }}{{ $promoteur->email ? ' ('.$promoteur->email.')' : '' }}
            </option>
        @endforeach
    </select>
    <x-field-error name="promoter_id" />
    <div class="form-text">Le promoteur choisi sera propriétaire de l'événement et le verra dans son espace.</div>
</section>
