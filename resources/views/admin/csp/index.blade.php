{{-- Rapports CSP : lecture seule. Aucune requête ni calcul ici (App\Services\Stats\CspViolationReport). --}}
@extends('layouts.admin')

@section('title', 'Rapports CSP - ClicBillet CI')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Rapports CSP</li>
@endsection

@section('content')
    <div class="mb-3">
        <h2 class="fw-bold mb-1">Rapports CSP</h2>
        <p class="text-muted mb-0">Mode rapport seulement : rien n'est bloqué. Chaque ligne est un point à corriger avant le passage en mode strict. Aucune donnée personnelle n'est conservée (ni adresse IP, ni navigateur, ni contenu de script, ni paramètres d'adresse).</p>
    </div>

    @unless($reportOnly)
        <div class="alert alert-warning" role="status">L'envoi de la politique stricte en rapport est désactivé (<code>CSP_REPORT_ONLY=false</code>) : aucun nouveau rapport n'arrive.</div>
    @endunless

    @if(! $report['available'])
        <div class="alert alert-info" role="status">— Le stockage des rapports n'est pas disponible (migration non appliquée).</div>
    @elseif($report['rows'] === [])
        <div class="alert alert-info" role="status">Aucun rapport reçu pour l'instant.</div>
    @else
        <p class="text-muted">{{ count($report['rows']) }} point(s) distinct(s), {{ $report['total_hits'] }} signalement(s) au total, du plus fréquent au moins fréquent.</p>

        @foreach($report['rows'] as $row)
            <article class="csp-entry">
                <div class="csp-head">
                    <span class="csp-directive">{{ $row['directive'] }}</span>
                    <span class="csp-hits">{{ $row['hits'] }} fois</span>
                </div>
                <dl class="csp-details">
                    <dt>Ressource bloquée</dt>
                    <dd>{{ $row['blocked'] }}</dd>
                    <dt>Page</dt>
                    <dd>{{ $row['path'] }}</dd>
                    <dt>Dernier signalement</dt>
                    <dd>{{ $row['last_seen'] }}</dd>
                </dl>
            </article>
        @endforeach
    @endif
@endsection
