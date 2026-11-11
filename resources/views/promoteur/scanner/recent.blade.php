@extends('layouts.promoteur')

@section('title', 'Scans récents')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="h3 fw-bold mb-0">Scans récents</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('promoteur.scanner.index') }}" class="btn btn-primary" style="min-height:44px">Scanner</a>
            <a href="{{ route('promoteur.scanner.search') }}" class="btn btn-outline-primary" style="min-height:44px">Rechercher</a>
        </div>
    </div>

    @if($recentScans->isEmpty())
        <div class="alert alert-info">Aucun billet scanné pour le moment.</div>
    @else
        <ul class="list-group mb-3">
            @foreach($recentScans as $ticket)
                <x-promoteur.ticket-row :ticket="$ticket" :show-used-at="true" />
            @endforeach
        </ul>
        {{ $recentScans->links() }}
    @endif
</div>
@endsection
