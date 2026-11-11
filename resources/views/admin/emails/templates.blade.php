@extends('layouts.admin')

@section('title', "Modèles d'emails")

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.emails.dashboard') }}">Emails</a></li>
    <li class="breadcrumb-item active">Modèles</li>
@endsection

@section('content')
<div class="admin-page">
    <x-admin.page-header title="Modèles d'emails" :back="route('admin.emails.dashboard')" />

    <div class="alert alert-info" role="note">
        Les contenus sont définis dans le code de l'application ; cette liste est en lecture seule.
    </div>

    <div class="list-group">
        @foreach($templates as $key => $label)
            <div class="list-group-item d-flex justify-content-between align-items-center gap-2">
                <span>{{ $label }}</span>
                <code class="small">{{ $key }}</code>
            </div>
        @endforeach
    </div>
</div>
@endsection
