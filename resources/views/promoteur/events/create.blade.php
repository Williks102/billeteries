@extends('layouts.promoteur')

@section('title', 'Créer un événement')

@section('content')
<div class="mx-auto" style="max-width: 880px;">
    <div class="mb-3">
        <h1 class="h3 mb-1">Créer un événement</h1>
        <p class="text-muted mb-0">Une seule page : l'événement et ses billets. Vous pouvez enregistrer un brouillon et compléter plus tard.</p>
    </div>

    @include('events._form', [
        'action' => route('promoteur.events.store'),
        'categories' => $categories,
        'defaults' => $defaults,
        'cancelUrl' => route('promoteur.events.index'),
    ])
</div>
@endsection
