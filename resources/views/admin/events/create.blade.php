@extends('layouts.admin')

@section('title', 'Créer un événement')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.events.index') }}">Événements</a></li>
    <li class="breadcrumb-item active" aria-current="page">Créer</li>
@endsection

@section('content')
<div class="admin-page">
    <x-admin.page-header title="Créer un événement" subtitle="Une seule page : le promoteur, l'événement et ses billets. Brouillon ou publication." :back="route('admin.events.index')" backLabel="Événements" />

    @include('events._form', [
        'action' => route('admin.events.store'),
        'categories' => $categories,
        'defaults' => $defaults,
        'cancelUrl' => route('admin.events.index'),
        'topFields' => 'admin.events._promoter-field',
    ])
</div>
@endsection
