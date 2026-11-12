@extends('layouts.promoteur')

@section('title', "Statistiques d'utilisation")

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
        <h1 class="h3 fw-bold mb-0">Statistiques d'utilisation</h1>
        <a href="{{ route('promoteur.scanner.index') }}" class="btn btn-outline-dark" style="min-height:44px">Retour au scanner</a>
    </div>

    <x-period-filter :action="route('promoteur.scanner.stats')" :period="$period" />

    <x-stat-grid cols="3" label="Utilisation des billets">
        <x-stat-card label="Billets vendus" :value="$summary['sold']" hint="Payés ou utilisés, commandes payées" />
        <x-stat-card label="Scannés" :value="$summary['scanned']" hint="Billets déjà utilisés" />
        <x-stat-card label="Taux d'utilisation" unit="%" :value="$summary['usage_rate']" hint="Scannés / vendus" />
    </x-stat-grid>

    <h2 class="h5">Par événement : {{ $period->label }}</h2>
    @if($eventStats->isNotEmpty())
        <div class="table-responsive">
            <table class="table table-cards align-middle">
                <thead><tr><th>Événement</th><th>Billets vendus</th><th>Scannés</th><th>Taux d'utilisation</th></tr></thead>
                <tbody>
                @foreach($eventStats as $row)
                    <tr>
                        <td data-label="Événement" class="text-break">{{ $row['title'] }}</td>
                        <td data-label="Billets vendus">{{ $row['sold'] }}</td>
                        <td data-label="Scannés">{{ $row['scanned'] }}</td>
                        <td data-label="Taux d'utilisation">{{ \App\Helpers\CurrencyHelper::formatPercent($row['usage_rate']) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="text-muted">Aucun événement.</p>
    @endif
</div>
@endsection
