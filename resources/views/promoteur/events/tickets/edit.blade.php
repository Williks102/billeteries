@extends('layouts.promoteur')

@section('title', 'Modifier le billet')

@section('content')
<div class="tt-page mx-auto">
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-3">
        <div>
            <h1 class="h3 mb-1">Modifier « {{ $ticketType->name }} »</h1>
            <p class="text-muted mb-0">{{ $event->title }} · {{ $ticketType->quantity_sold }} billet(s) déjà vendu(s)</p>
        </div>
        <a href="{{ route('promoteur.events.tickets.index', $event) }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2" aria-hidden="true"></i>Retour aux billets
        </a>
    </div>

    @include('promoteur.events.tickets._form', ['mode' => 'edit'])
</div>
@endsection
