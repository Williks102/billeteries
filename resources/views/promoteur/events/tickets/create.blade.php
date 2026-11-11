@extends('layouts.promoteur')

@section('title', 'Types de billets')

@section('content')
<div class="tt-page mx-auto">
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-3">
        <div>
            <p class="text-muted small mb-1">Étape 2 sur 3 · Types de billets</p>
            <h1 class="h3 mb-1">Configurer les types de billets</h1>
            <p class="text-muted mb-0">« {{ $event->title }} »</p>
        </div>
        <a href="{{ route('promoteur.events.show', $event) }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2" aria-hidden="true"></i>Retour à l'événement
        </a>
    </div>

    <details class="tt-card">
        <summary>Récapitulatif de l'événement</summary>
        <dl class="row mb-0 mt-2">
            <dt class="col-4 col-sm-3">Événement</dt><dd class="col-8 col-sm-9">{{ $event->title }}</dd>
            <dt class="col-4 col-sm-3">Date</dt><dd class="col-8 col-sm-9">{{ $event->event_date ? $event->event_date->format('d/m/Y') : 'Non définie' }}</dd>
            <dt class="col-4 col-sm-3">Lieu</dt><dd class="col-8 col-sm-9 mb-0">{{ $event->venue }}</dd>
        </dl>
    </details>

    <details class="tt-card">
        <summary>Modèles prédéfinis (remplissage rapide)</summary>
        <div class="tt-presets" data-presets>
            <button type="button" class="btn btn-outline-secondary tt-preset" data-preset
                    data-name="Standard" data-description="Billet d'accès standard" data-price="5000" data-quantity="100" data-max="5">
                <span>Standard</span><span class="fw-bold">5 000 FCFA</span>
            </button>
            <button type="button" class="btn btn-outline-secondary tt-preset" data-preset
                    data-name="VIP" data-description="Accès privilégié avec avantages" data-price="10000" data-quantity="50" data-max="3">
                <span>VIP</span><span class="fw-bold">10 000 FCFA</span>
            </button>
            <button type="button" class="btn btn-outline-secondary tt-preset" data-preset
                    data-name="Early Bird" data-description="Tarif préférentiel (vente anticipée)" data-price="3500" data-quantity="75" data-max="5">
                <span>Early Bird</span><span class="fw-bold">3 500 FCFA</span>
            </button>
            <button type="button" class="btn btn-outline-secondary tt-preset" data-preset
                    data-name="Groupe" data-description="Tarif de groupe (minimum 5 personnes)" data-price="4000" data-quantity="200" data-max="10">
                <span>Groupe</span><span class="fw-bold">4 000 FCFA</span>
            </button>
        </div>
    </details>

    @include('promoteur.events.tickets._form', ['mode' => 'create'])
</div>
@endsection
