@extends('layouts.admin')

@section('title', "Modèles d'emails")

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.emails.dashboard') }}">Emails</a></li>
    <li class="breadcrumb-item active" aria-current="page">Modèles</li>
@endsection

@section('content')
<div class="admin-page">
    <x-admin.page-header title="Modèles d'emails" :back="route('admin.emails.dashboard')" />

    <div class="alert alert-info" role="note">
        Ces modèles correspondent aux messages réellement envoyés par l'application. Leur contenu est défini dans le code ; cette liste est en lecture seule.
    </div>

    <div class="list-group">
        @foreach($templates as $class => $template)
            <div class="list-group-item d-flex justify-content-between align-items-center gap-2">
                <span>{{ $template['label'] }}</span>
                <span class="text-muted small">{{ $template['audience'] }}</span>
            </div>
        @endforeach
    </div>
</div>
@endsection
