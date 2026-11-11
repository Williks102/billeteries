@extends('layouts.promoteur')

@section('title', 'Rechercher un billet')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="h3 fw-bold mb-0">Rechercher un billet</h1>
        <a href="{{ route('promoteur.scanner.index') }}" class="btn btn-outline-primary" style="min-height:44px">Retour au scanner</a>
    </div>

    <form method="GET" action="{{ route('promoteur.scanner.search') }}" class="mb-3" role="search">
        <label for="search" class="form-label fw-semibold">Code du billet, nom de l'acheteur ou événement</label>
        <div class="input-group">
            <input type="search" id="search" name="search" value="{{ $searchTerm }}"
                   class="form-control" style="min-height:44px;font-size:16px"
                   autocomplete="off" autocapitalize="characters" enterkeyhint="search" placeholder="Ex. TKT-AB12CD34">
            <button type="submit" class="btn btn-primary" style="min-height:44px">Rechercher</button>
        </div>
    </form>

    @if($results === null)
        <p class="text-muted">Saisissez un terme pour rechercher parmi les billets de vos événements.</p>
    @elseif($results->isEmpty())
        <div class="alert alert-warning" role="status">Aucun billet trouvé pour « {{ $searchTerm }} ».</div>
    @else
        <p class="text-muted small" role="status">{{ $results->total() }} résultat(s)</p>
        <ul class="list-group mb-3">
            @foreach($results as $ticket)
                <x-promoteur.ticket-row :ticket="$ticket" :show-used-at="$ticket->status === 'used'" />
            @endforeach
        </ul>
        {{ $results->appends(['search' => $searchTerm])->links() }}
    @endif
</div>
@endsection
