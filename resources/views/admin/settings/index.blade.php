@extends('layouts.admin')

@section('title', 'Configuration')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Configuration</li>
@endsection

@section('content')
@php
    $yesNo = fn ($v) => $v ? 'Oui' : 'Non';
@endphp
<div class="admin-page">
    <x-admin.page-header title="Configuration" subtitle="Vue d'ensemble de la configuration (lecture seule)" />

    <div class="alert alert-info" role="note">
        Aucune valeur sensible (clés, mots de passe) n'est affichée. La configuration se modifie dans l'environnement du serveur.
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3"><x-admin.stat-card label="Utilisateurs" :value="number_format($globalStats['total_users'], 0, ',', ' ')" /></div>
        <div class="col-6 col-lg-3"><x-admin.stat-card label="Événements" :value="number_format($globalStats['total_events'], 0, ',', ' ')" /></div>
        <div class="col-6 col-lg-3"><x-admin.stat-card label="Commandes" :value="number_format($globalStats['total_orders'], 0, ',', ' ')" /></div>
        <div class="col-6 col-lg-3"><x-admin.stat-card label="Ventes (commandes payées)" :value="\App\Helpers\CurrencyHelper::formatFCFA($globalStats['total_revenue'])" /></div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header"><h2 class="h5 mb-0">Commission plateforme</h2></div>
                <div class="card-body">
                    @php
                        $commissionRate = \App\Services\CommissionRate::default();
                        $commissionFromDb = \App\Services\CommissionRate::source() === 'database';
                        $fmtRate = fn ($r) => rtrim(rtrim(number_format((float) $r, 2, ',', ' '), '0'), ',');
                    @endphp
                    <p class="mb-1">Taux par défaut actuel : <strong>{{ $fmtRate($commissionRate) }} %</strong></p>
                    <p class="small mb-3">
                        Source :
                        @if($commissionFromDb)
                            défini dans l'administration
                        @else
                            valeur par défaut du serveur (<code>DEFAULT_COMMISSION_RATE</code>)
                        @endif
                    </p>
                    <form method="POST" action="{{ route('admin.settings.store') }}" class="commission-rate-form" novalidate>
                        @csrf
                        <input type="hidden" name="section" value="commission">
                        <label for="commission_rate" class="form-label">Nouveau taux de commission (%)</label>
                        <input type="number" inputmode="decimal" step="0.01" min="0" max="100" id="commission_rate" name="commission_rate"
                               value="{{ old('commission_rate', $commissionRate) }}"
                               class="form-control commission-rate-input @error('commission_rate') is-invalid @enderror"
                               aria-describedby="commission_rate_help" required>
                        @error('commission_rate')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        <button type="submit" class="btn btn-orange commission-rate-submit mt-3">Enregistrer le taux</button>
                    </form>
                    @if($commissionFromDb)
                        <form method="POST" action="{{ route('admin.settings.store') }}" class="mt-2">
                            @csrf
                            <input type="hidden" name="section" value="commission">
                            <input type="hidden" name="reset" value="1">
                            <button type="submit" class="btn btn-outline-secondary commission-rate-submit">Revenir à la valeur par défaut du serveur</button>
                        </form>
                    @endif
                    <p id="commission_rate_help" class="text-muted small mt-3 mb-0">
                        S'applique aux nouvelles commandes ; les commandes déjà payées gardent leur commission ;
                        les taux par promoteur ou par catégorie, s'il y en a, priment. Prélevé sur le prix des billets.
                    </p>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header"><h2 class="h5 mb-0">Application</h2></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-6">Nom</dt><dd class="col-6">{{ $currentConfig['app_name'] }}</dd>
                        <dt class="col-6">Environnement</dt><dd class="col-6">{{ $currentConfig['app_env'] }}</dd>
                        <dt class="col-6">Mode debug</dt><dd class="col-6">{{ $yesNo($currentConfig['app_debug']) }}</dd>
                        <dt class="col-6">URL</dt><dd class="col-6 text-break">{{ $currentConfig['app_url'] }}</dd>
                        <dt class="col-6">Fuseau horaire</dt><dd class="col-6">{{ $currentConfig['timezone'] }}</dd>
                        <dt class="col-6">Email (pilote)</dt><dd class="col-6">{{ $currentConfig['mail_driver'] }}</dd>
                        <dt class="col-6">Email expéditeur</dt><dd class="col-6 text-break">{{ $currentConfig['mail_from'] }}</dd>
                        <dt class="col-6">File d'attente</dt><dd class="col-6">{{ $currentConfig['queue_driver'] }}</dd>
                        <dt class="col-6">Cache</dt><dd class="col-6">{{ $currentConfig['cache_driver'] }}</dd>
                        <dt class="col-6">Sessions</dt><dd class="col-6">{{ $currentConfig['session_driver'] }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header"><h2 class="h5 mb-0">Système</h2></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-6 col-md-3">PHP</dt><dd class="col-6 col-md-3">{{ $systemInfo['php_version'] }}</dd>
                        <dt class="col-6 col-md-3">Laravel</dt><dd class="col-6 col-md-3">{{ $systemInfo['laravel_version'] }}</dd>
                        <dt class="col-6 col-md-3">Mémoire PHP</dt><dd class="col-6 col-md-3">{{ $systemInfo['memory_limit'] }}</dd>
                        <dt class="col-6 col-md-3">Taille max. d'envoi</dt><dd class="col-6 col-md-3">{{ $systemInfo['upload_max_filesize'] }}</dd>
                        <dt class="col-6 col-md-3">Espace disque libre</dt><dd class="col-6 col-md-3">{{ $systemInfo['disk_space'] }}</dd>
                        <dt class="col-6 col-md-3">Base de données</dt><dd class="col-6 col-md-3">{{ $systemInfo['database_size'] }}</dd>
                        <dt class="col-6 col-md-3">Stockage utilisé</dt><dd class="col-6 col-md-3">{{ $globalStats['storage_used'] }}</dd>
                        <dt class="col-6 col-md-3">Fichiers de logs</dt><dd class="col-6 col-md-3">{{ $globalStats['log_files_count'] }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
