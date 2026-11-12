{{--
    Formulaire unique de création d'événement (événement + billets, un seul envoi). Réutilisable par l'admin.
    Paramètres :
      $action      URL de soumission (POST)
      $categories  catégories (id, name)
      $defaults    EventFormDefaults::values()
      $cancelUrl   lien « Annuler »
      $topFields   (optionnel) nom d'une vue incluse en tête du formulaire (ex. choix du promoteur côté admin ;
                   l'admin passera alors le promoteur EXPLICITEMENT à CreateEventAction)
    Boutons : « Enregistrer en brouillon » (action=draft) et « Publier » (action=publish, soumis à la checklist).
--}}
@php
    $event = new \App\Models\Event();
    $blocks = old('ticket_types');
    if (! is_array($blocks)) {
        $blocks = [0 => [
            'name' => $defaults['ticket_name'], 'quantity_available' => $defaults['quantity_available'],
            'max_per_order' => $defaults['max_per_order'],
        ]];
    }
    $nextIndex = $blocks === [] ? 1 : ((int) max(array_keys($blocks)) + 1);
    $advancedErrors = collect(['description', 'address', 'end_time', 'terms_conditions'])->contains(fn ($k) => $errors->has($k));
    $advancedFilled = filled(old('description')) || filled(old('address')) || filled(old('end_time')) || filled(old('terms_conditions'));
    $describe = fn (string $f, string $hint = '') => trim(($hint !== '' ? $f.'-hint ' : '').($errors->has($f) ? $f.'-error' : ''));
@endphp

<form class="ev-form tt-form" action="{{ $action }}" method="POST" enctype="multipart/form-data" autocomplete="off"
      data-ticket-form data-mode="create" data-event-form>
    @csrf
    {{-- Validation par la touche Entrée = brouillon (jamais une publication par accident) --}}
    <button type="submit" name="action" value="draft" class="visually-hidden" tabindex="-1" aria-hidden="true"></button>

    @if ($errors->any())
        <div class="alert alert-danger" role="alert" id="tt-error-summary" tabindex="-1">
            <strong>Le formulaire contient des erreurs :</strong>
            <ul class="mb-0 mt-2 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @isset($topFields)
        @include($topFields)
    @endisset

    <section class="ev-card" aria-labelledby="ev-essential">
        <h2 id="ev-essential" class="ev-title">L'essentiel</h2>
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label" for="title">Titre <span class="text-danger" aria-hidden="true">*</span></label>
                <input type="text" id="title" name="title" value="{{ old('title') }}" maxlength="255" required
                       enterkeyhint="next" placeholder="Ex : Soirée Afrobeat"
                       class="form-control @error('title') is-invalid @enderror"
                       @error('title') aria-invalid="true" aria-describedby="title-error" @enderror>
                <x-field-error name="title" />
            </div>

            <div class="col-12">
                <label class="form-label" for="category_id">Catégorie <span class="text-danger" aria-hidden="true">*</span></label>
                <select id="category_id" name="category_id" required
                        class="form-select @error('category_id') is-invalid @enderror"
                        @error('category_id') aria-invalid="true" aria-describedby="category_id-error" @enderror>
                    <option value="">Choisir une catégorie</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) old('category_id') === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
                <x-field-error name="category_id" />
            </div>

            <div class="col-7 col-sm-6">
                <label class="form-label" for="event_date">Date <span class="text-danger" aria-hidden="true">*</span></label>
                <input type="date" id="event_date" name="event_date" value="{{ old('event_date', $defaults['event_date']) }}"
                       min="{{ now()->format('Y-m-d') }}" required
                       class="form-control @error('event_date') is-invalid @enderror"
                       @error('event_date') aria-invalid="true" aria-describedby="event_date-error" @enderror>
                <x-field-error name="event_date" />
            </div>

            <div class="col-5 col-sm-6">
                <label class="form-label" for="event_time">Heure de début <span class="text-danger" aria-hidden="true">*</span></label>
                <input type="time" id="event_time" name="event_time" value="{{ old('event_time', $defaults['event_time']) }}" required
                       class="form-control @error('event_time') is-invalid @enderror"
                       @error('event_time') aria-invalid="true" aria-describedby="event_time-error" @enderror>
                <x-field-error name="event_time" />
            </div>

            <div class="col-12">
                <label class="form-label" for="venue">Lieu <span class="text-danger" aria-hidden="true">*</span></label>
                <input type="text" id="venue" name="venue" value="{{ old('venue') }}" maxlength="255" required
                       enterkeyhint="next" placeholder="Ex : Palais de la Culture, Abidjan"
                       class="form-control @error('venue') is-invalid @enderror"
                       @error('venue') aria-invalid="true" aria-describedby="venue-error" @enderror>
                <x-field-error name="venue" />
            </div>

            <div class="col-12">
                <label class="form-label" for="image">Image de l'événement <span class="text-muted small">(facultatif pour un brouillon)</span></label>
                <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/gif"
                       class="form-control @error('image') is-invalid @enderror"
                       aria-describedby="{{ $describe('image', 'x') }}" @error('image') aria-invalid="true" @enderror>
                <div id="image-hint" class="form-text">Obligatoire pour publier. JPEG, PNG ou GIF, 2 Mo maximum.</div>
                <x-field-error name="image" />
            </div>
        </div>
    </section>

    <section class="ev-card" aria-labelledby="ev-tickets">
        <h2 id="ev-tickets" class="ev-title">Billets</h2>
        <p class="text-muted small mb-3">Un billet « Standard » est prêt : indiquez seulement son prix (0 pour un billet gratuit). La vente ferme au début de l'événement.</p>

        <div data-ticket-blocks data-next-index="{{ $nextIndex }}">
            @foreach ($blocks as $i => $values)
                @include('promoteur.events.tickets._fields', [
                    'prefix' => 'ticket_types['.$i.']',
                    'errKey' => 'ticket_types.'.$i.'.',
                    'uid' => 'tt-'.$i,
                    'values' => $values,
                    'event' => $event,
                    'isCreate' => true,
                    'inline' => true,
                    'minQuantity' => 1,
                ])
            @endforeach
        </div>

        <template data-ticket-template>
            @include('promoteur.events.tickets._fields', [
                'prefix' => 'ticket_types[__INDEX__]',
                'errKey' => '__none__.',
                'uid' => 'tt-__INDEX__',
                'values' => ['quantity_available' => $defaults['quantity_available'], 'max_per_order' => $defaults['max_per_order']],
                'event' => $event,
                'isCreate' => true,
                'inline' => true,
                'minQuantity' => 1,
            ])
        </template>

        <button type="button" class="btn btn-outline-secondary w-100" data-add-block>
            <i class="fas fa-plus me-2" aria-hidden="true"></i>Ajouter un billet
        </button>
        <div class="tt-presets" data-presets>
            <button type="button" class="btn btn-outline-secondary tt-preset" data-preset
                    data-name="Standard" data-description="" data-price="" data-quantity="{{ $defaults['quantity_available'] }}" data-max="{{ $defaults['max_per_order'] }}">
                <span>Modèle Standard</span>
            </button>
            <button type="button" class="btn btn-outline-secondary tt-preset" data-preset
                    data-name="VIP" data-description="Accès privilégié avec avantages" data-price="" data-quantity="50" data-max="5">
                <span>Modèle VIP</span>
            </button>
            <button type="button" class="btn btn-outline-secondary tt-preset" data-preset
                    data-name="Gratuit" data-description="" data-price="0" data-quantity="{{ $defaults['quantity_available'] }}" data-max="{{ $defaults['max_per_order'] }}">
                <span>Modèle Gratuit</span>
            </button>
        </div>
    </section>

    <details class="ev-card ev-advanced" @if ($advancedErrors || $advancedFilled) open @endif>
        <summary>Options avancées <span class="text-muted small">(description, adresse, heure de fin, conditions)</span></summary>
        <div class="row g-3 mt-0">
            <div class="col-12">
                <label class="form-label" for="description">Description <span class="text-muted small">(facultatif)</span></label>
                <textarea id="description" name="description" rows="4"
                          class="form-control @error('description') is-invalid @enderror"
                          placeholder="Programme, artistes, informations utiles"
                          @error('description') aria-invalid="true" aria-describedby="description-error" @enderror>{{ old('description') }}</textarea>
                <x-field-error name="description" />
            </div>

            <div class="col-12 col-sm-6">
                <label class="form-label" for="address">Adresse précise <span class="text-muted small">(vide : le lieu)</span></label>
                <input type="text" id="address" name="address" value="{{ old('address') }}"
                       class="form-control @error('address') is-invalid @enderror"
                       @error('address') aria-invalid="true" aria-describedby="address-error" @enderror>
                <x-field-error name="address" />
            </div>

            <div class="col-12 col-sm-6">
                <label class="form-label" for="end_time">Heure de fin <span class="text-muted small">(facultatif)</span></label>
                <input type="time" id="end_time" name="end_time" value="{{ old('end_time') }}"
                       class="form-control @error('end_time') is-invalid @enderror"
                       @error('end_time') aria-invalid="true" aria-describedby="end_time-error" @enderror>
                <x-field-error name="end_time" />
            </div>

            <div class="col-12">
                <label class="form-label" for="terms_conditions">Conditions <span class="text-muted small">(facultatif)</span></label>
                <textarea id="terms_conditions" name="terms_conditions" rows="3"
                          class="form-control @error('terms_conditions') is-invalid @enderror"
                          placeholder="Âge minimum, remboursement, objets interdits"
                          @error('terms_conditions') aria-invalid="true" aria-describedby="terms_conditions-error" @enderror>{{ old('terms_conditions') }}</textarea>
                <x-field-error name="terms_conditions" />
            </div>
        </div>
        <p class="text-muted small mt-3 mb-0">Maximum par commande et dates de vente de chaque billet : dans « Options du billet ».</p>
    </details>

    <div class="action-bar action-bar--sticky">
        <button type="submit" name="action" value="publish" class="btn btn-orange" data-submit-once>
            <i class="fas fa-paper-plane" aria-hidden="true"></i>Publier
        </button>
        <button type="submit" name="action" value="draft" class="btn btn-outline-secondary" data-submit-once>
            Enregistrer en brouillon
        </button>
        <a href="{{ $cancelUrl }}" class="btn action-bar__cancel">Annuler</a>
    </div>
    <p class="text-muted small mt-2">« Publier » n'est possible que si l'événement est complet (billet valide, date future, image). Sinon, il est enregistré en brouillon et la liste de ce qui manque s'affiche.</p>
</form>

@push('styles')
<style>
    .ev-form { max-width: 880px; margin-inline: auto; }
    .ev-card { background: var(--surface); border: 1px solid var(--line); border-radius: var(--radius); padding: var(--space-4); margin-bottom: var(--space-4); min-width: 0; }
    .ev-title { font-size: 1.15rem; font-weight: 600; margin: 0 0 var(--space-3); }
    .ev-advanced > summary { cursor: pointer; font-weight: 600; min-height: var(--touch-min); display: flex; align-items: center; flex-wrap: wrap; gap: .25rem .5rem; }
    .ev-form .form-control, .ev-form .form-select, .ev-form .btn, .ev-form .form-control::placeholder { font-size: 16px; }
    .ev-form .form-control, .ev-form .form-select { min-height: var(--touch-min); }
    .ev-form .btn-orange { background: var(--brand); border-color: var(--brand); color: #fff; }
    .ev-form .btn-orange:hover, .ev-form .btn-orange:focus { background: var(--brand-hover); border-color: var(--brand-hover); color: #fff; }
    .ev-form .form-text, .ev-form .invalid-feedback { font-size: 14px; }
    .ev-form .tt-block { position: relative; border: 1px solid var(--line); border-left: 4px solid var(--brand); border-radius: var(--radius); padding: var(--space-3); margin: 0 0 var(--space-3); min-width: 0; }
    .ev-form .tt-block-title { float: none; width: auto; font-size: 1rem; font-weight: 600; margin: 0 0 .75rem; padding-right: 56px; }
    .ev-form .tt-accent { color: var(--brand); }
    .ev-form .tt-remove { position: absolute; top: .5rem; right: .5rem; min-width: var(--touch-min); }
    .ev-form .tt-adv > summary { cursor: pointer; min-height: var(--touch-min); display: flex; align-items: center; flex-wrap: wrap; gap: .25rem .5rem; font-weight: 600; }
    .ev-form .tt-revenue { display: flex; justify-content: space-between; gap: .5rem; flex-wrap: wrap; background: var(--canvas); border-radius: 8px; padding: .75rem 1rem; margin-top: 1rem; }
    .ev-form .btn { min-height: var(--touch-min); display: inline-flex; align-items: center; justify-content: center; gap: .5rem; }
    .ev-form .tt-presets { display: grid; grid-template-columns: 1fr; gap: .5rem; margin-top: .75rem; }
    .ev-form .tt-preset { width: 100%; }
    .ev-form :focus-visible, .ev-form summary:focus-visible { outline: 3px solid #1a73e8; outline-offset: 2px; }
    @media (min-width: 576px) { .ev-form .tt-presets { grid-template-columns: repeat(3, 1fr); } .ev-card { padding: var(--space-6); } }
</style>
@endpush

@push('scripts')
<script src="{{ asset('js/promoteur-tickets-form.js') }}" defer></script>
<script src="{{ asset('js/event-form-submit-once.js') }}" defer></script>
@endpush
