{{-- Journal d'activité : lecture seule. Aucune requête ni calcul ici (App\Services\Stats\ActivityLogQuery). --}}
@extends('layouts.admin')

@section('title', "Journal d'activité - ClicBillet CI")

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Journal d'activité</li>
@endsection

@section('content')
    <div class="mb-3">
        <h2 class="fw-bold mb-1">Journal d'activité</h2>
        <p class="text-muted mb-0">Qui a fait quoi, et quand. Le journal est en lecture seule : aucune entrée ne peut être modifiée ni supprimée.</p>
    </div>

    <form method="GET" action="{{ route('admin.activity.index') }}" class="activity-filters row g-2 align-items-end mb-3">
        <div class="col-12 col-sm-6 col-lg-3">
            <label for="family" class="form-label mb-1">Type d'action</label>
            <select id="family" name="family" class="form-select">
                <option value="">Toutes les actions</option>
                @foreach($families as $key => $label)
                    <option value="{{ $key }}" @selected(($filters['family'] ?? '') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <label for="actor" class="form-label mb-1">Acteur</label>
            <select id="actor" name="actor" class="form-select">
                <option value="">Tous les acteurs</option>
                <option value="system" @selected(($filters['actor'] ?? '') === 'system')>Système</option>
                @foreach($actors as $actor)
                    <option value="{{ $actor['id'] }}" @selected(($filters['actor'] ?? '') === (string) $actor['id'])>{{ $actor['name'] }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-sm-6 col-lg-2">
            <label for="period" class="form-label mb-1">Période</label>
            <select id="period" name="period" class="form-select">
                @foreach(['today' => "Aujourd'hui", '7d' => '7 derniers jours', '30d' => '30 derniers jours', 'this_month' => 'Ce mois-ci', 'last_month' => 'Mois dernier', 'this_year' => 'Cette année', 'all' => 'Toute la période', 'custom' => 'Dates personnalisées'] as $key => $label)
                    <option value="{{ $key }}" @selected($period->key === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-sm-3 col-lg-2">
            <label for="start_date" class="form-label mb-1">Du</label>
            <input type="date" id="start_date" name="start_date" class="form-control" value="{{ $period->key === 'custom' ? $period->start->format('Y-m-d') : '' }}">
        </div>
        <div class="col-6 col-sm-3 col-lg-2">
            <label for="end_date" class="form-label mb-1">Au</label>
            <input type="date" id="end_date" name="end_date" class="form-control" value="{{ $period->key === 'custom' ? $period->end->format('Y-m-d') : '' }}">
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-primary activity-submit">Afficher</button>
        </div>
    </form>
    @error('period')
        <div class="alert alert-danger py-2" role="alert">{{ $message }}</div>
    @enderror
    <p class="text-muted">Période affichée : <strong>{{ $period->label }}</strong></p>

    @forelse($logs as $log)
        <article class="activity-entry">
            <div class="activity-head">
                <span class="activity-action">{{ $log['label'] }}</span>
                <time class="activity-time">{{ $log['when'] }}</time>
            </div>
            <p class="activity-description">{{ $log['description'] }}</p>
            @if($log['details'] !== [])
                <ul class="activity-details">
                    @foreach($log['details'] as $line)
                        <li>{{ $line }}</li>
                    @endforeach
                </ul>
            @endif
            <div class="activity-foot">
                <span class="activity-actor">{{ $log['actor'] }}@if($log['role']) ({{ $log['role'] }})@endif</span>
                @if($log['subject_url'])
                    <a href="{{ $log['subject_url'] }}" class="activity-link">Voir l'élément concerné</a>
                @endif
            </div>
        </article>
    @empty
        <div class="alert alert-info" role="status">Aucune activité enregistrée pour ces critères.</div>
    @endforelse

    <div class="mt-3">
        {{ $logs->links() }}
    </div>
@endsection
