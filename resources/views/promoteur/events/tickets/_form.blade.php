{{--
    Formulaire des types de billets, partagé par create (plusieurs blocs) et edit (un bloc).
    Paramètres : $event, $mode ('create'|'edit'), $ticketType (edit seulement).
--}}
@php
    $isCreate = $mode === 'create';
    $eventDate = $event->event_date ? $event->event_date->format('Y-m-d') : '';
    $today = now()->format('Y-m-d');

    if ($isCreate) {
        // Blocs à afficher : saisie précédente (après erreur) ou un bloc par défaut.
        $blocks = old('ticket_types');
        if (! is_array($blocks) || $blocks === []) {
            $blocks = [0 => [
                'quantity_available' => 100, 'max_per_order' => 10,
                'sale_start_date' => $today, 'sale_end_date' => $eventDate,
            ]];
        }
        $nextIndex = (int) max(array_keys($blocks)) + 1;
    } else {
        $fmtDate = fn ($d) => $d ? $d->format('Y-m-d') : '';
        $blocks = [0 => [
            'name' => old('name', $ticketType->name),
            'description' => old('description', $ticketType->description),
            'price' => old('price', $ticketType->price),
            'quantity_available' => old('quantity_available', $ticketType->quantity_available),
            'max_per_order' => old('max_per_order', $ticketType->max_per_order),
            'sale_start_date' => old('sale_start_date', $fmtDate($ticketType->sale_start_date)),
            'sale_end_date' => old('sale_end_date', $fmtDate($ticketType->sale_end_date)),
        ]];
        $nextIndex = 1;
    }
@endphp

<form class="tt-form"
      action="{{ $isCreate ? route('promoteur.events.tickets.store', $event) : route('promoteur.events.tickets.update', [$event, $ticketType]) }}"
      method="POST" autocomplete="off" data-ticket-form data-mode="{{ $mode }}">
    @csrf
    @unless ($isCreate)
        @method('PUT')
    @endunless

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

    <div data-ticket-blocks data-next-index="{{ $nextIndex }}">
        @foreach ($blocks as $i => $values)
            @include('promoteur.events.tickets._fields', [
                'prefix' => $isCreate ? 'ticket_types['.$i.']' : '',
                'errKey' => $isCreate ? 'ticket_types.'.$i.'.' : '',
                'uid' => 'tt-'.$i,
                'values' => $values,
                'event' => $event,
                'isCreate' => $isCreate,
                'minQuantity' => $isCreate ? 1 : max(1, (int) $ticketType->quantity_sold),
            ])
        @endforeach
    </div>

    @if ($isCreate)
        {{-- Modèle cloné par le JS (aucune donnée utilisateur) --}}
        <template data-ticket-template>
            @include('promoteur.events.tickets._fields', [
                'prefix' => 'ticket_types[__INDEX__]',
                'errKey' => '__none__.',
                'uid' => 'tt-__INDEX__',
                'values' => ['quantity_available' => 100, 'max_per_order' => 10, 'sale_start_date' => $today, 'sale_end_date' => $eventDate],
                'event' => $event,
                'isCreate' => true,
                'minQuantity' => 1,
            ])
        </template>

        <button type="button" class="btn btn-outline-secondary w-100 mb-3" data-add-block>
            <i class="fas fa-plus me-2" aria-hidden="true"></i>Ajouter un type de billet
        </button>
    @endif

    <div class="action-bar action-bar--sticky">
        <button type="submit" class="btn btn-orange">
            {{ $isCreate ? 'Créer les billets' : 'Enregistrer' }}
            <i class="fas fa-check" aria-hidden="true"></i>
        </button>
        <a href="{{ $isCreate ? route('promoteur.events.show', $event) : route('promoteur.events.tickets.index', $event) }}"
           class="btn btn-outline-secondary action-bar__cancel">{{ $isCreate ? 'Terminer plus tard' : 'Annuler' }}</a>
    </div>
</form>

@push('styles')
<style>
    .tt-page { max-width: 880px; }
    .tt-card { background: #fff; border-radius: 12px; box-shadow: 0 2px 6px rgba(0,0,0,.08); border-left: 4px solid var(--brand); padding: 1rem; margin-bottom: 1rem; }
    .tt-card > summary { cursor: pointer; font-weight: 600; min-height: 44px; display: flex; align-items: center; }
    .tt-accent { color: var(--brand); }
    .tt-form .form-control, .tt-form .btn, .tt-form .form-control::placeholder { font-size: 16px; }
    .tt-form .form-control { min-height: 44px; }
    .tt-form .btn { min-height: 44px; display: inline-flex; align-items: center; justify-content: center; }
    .tt-form .btn-orange { background: var(--brand); border-color: var(--brand); color: #fff; }
    .tt-form .btn-orange:hover, .tt-form .btn-orange:focus { background: var(--brand-hover); border-color: var(--brand-hover); color: #fff; }
    .tt-form .form-text, .tt-form .invalid-feedback { font-size: 14px; }
    .tt-block { position: relative; background: #fff; border-radius: 12px; box-shadow: 0 2px 6px rgba(0,0,0,.08); border-left: 4px solid var(--brand); padding: 1rem; margin: 0 0 1rem; min-width: 0; }
    .tt-block-title { float: none; width: auto; font-size: 1.1rem; font-weight: 600; margin: 0 0 .75rem; padding-right: 56px; }
    .tt-remove { position: absolute; top: .5rem; right: .5rem; min-width: 44px; }
    .tt-revenue { display: flex; justify-content: space-between; gap: .5rem; flex-wrap: wrap; background: #f1f3f5; border-radius: 8px; padding: .75rem 1rem; margin-top: 1rem; }
    .tt-presets { display: grid; grid-template-columns: 1fr; gap: .5rem; margin-top: .75rem; }
    .tt-preset { text-align: left; justify-content: space-between !important; width: 100%; }
    .tt-form :focus-visible, .tt-page summary:focus-visible { outline: 3px solid #1a73e8; outline-offset: 2px; }
    @media (min-width: 576px) { .tt-presets { grid-template-columns: repeat(2, 1fr); } .tt-block, .tt-card { padding: 1.5rem; } }
    @media (min-width: 992px) { .tt-presets { grid-template-columns: repeat(4, 1fr); } }
</style>
@endpush

@push('scripts')
<script src="{{ asset('js/promoteur-tickets-form.js') }}" defer></script>
@endpush
