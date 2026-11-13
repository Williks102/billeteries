@extends('layouts.admin')

@section('title', 'Emails')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Emails</li>
@endsection

@section('content')
<div class="admin-page">
    <x-admin.page-header title="Emails" subtitle="Configuration d'envoi (lecture seule) et message de test">
        <a href="{{ route('admin.emails.templates') }}" class="btn btn-outline-primary">
            <i class="fas fa-envelope-open-text" aria-hidden="true"></i> Modèles ({{ $templateCount }})
        </a>
    </x-admin.page-header>

    @if($mailerIsSimulated)
        <div class="alert alert-warning" role="alert">
            <strong>Aucun message n'est réellement envoyé.</strong>
            Le pilote d'envoi est « {{ $mailer }} » : les messages sont seulement écrits dans le journal du serveur.
            Pour envoyer de vrais e-mails, un service d'envoi doit être configuré dans l'environnement du serveur.
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header"><h2 class="h5 mb-0">Configuration d'envoi</h2></div>
                <div class="card-body">
                    <dl class="mb-0">
                        <dt>Pilote d'envoi</dt>
                        <dd>{{ $mailer }}</dd>
                        <dt>Adresse de l'expéditeur</dt>
                        <dd class="text-break">{{ $fromAddress !== '' ? $fromAddress : 'Non définie' }}</dd>
                        <dt>Nom de l'expéditeur</dt>
                        <dd>{{ $fromName !== '' ? $fromName : 'Non défini' }}</dd>
                    </dl>
                    <p class="text-muted small mt-3 mb-0">Aucun identifiant ni mot de passe n'est affiché. Le suivi des e-mails (envoyés, rebonds, ouvertures) n'existe pas : aucune statistique n'est présentée.</p>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header"><h2 class="h5 mb-0">Envoyer un email de test</h2></div>
                <div class="card-body">
                    <form id="test-email-form" action="{{ route('admin.emails.test') }}" method="POST" novalidate>
                        @csrf
                        <div class="mb-3">
                            <label for="email" class="form-label">Adresse destinataire</label>
                            <input type="email" id="email" name="email" class="form-control" required autocomplete="email"
                                   aria-describedby="test-email-result">
                        </div>
                        <div class="action-bar mt-0">
                            <button type="submit" class="btn btn-primary">Envoyer le test</button>
                        </div>
                        <div id="test-email-result" class="mt-3" role="status" aria-live="polite"></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/admin-emails-test.js') }}" defer></script>
@endpush
