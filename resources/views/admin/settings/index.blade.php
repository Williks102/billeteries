@extends('layouts.admin')

@section('title', 'Paramètres')

@section('breadcrumb')
    <li class="breadcrumb-item active">Paramètres</li>
@endsection

@section('content')
@php
    $yesNo = fn ($v) => $v ? 'Oui' : 'Non';
@endphp
<div class="admin-page">
    <x-admin.page-header title="Paramètres" subtitle="Vue d'ensemble de la configuration (lecture seule)" />

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
                    <p class="mb-1">Taux par défaut : <strong>{{ rtrim(rtrim(number_format((float) config('payments.default_commission_rate'), 2, ',', ' '), '0'), ',') }} %</strong></p>
                    <p class="text-muted small mb-0">
                        Prélevé sur le prix des billets. Les taux définis par promoteur ou par catégorie sont prioritaires.
                        Valeur issue de <code>DEFAULT_COMMISSION_RATE</code>.
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
