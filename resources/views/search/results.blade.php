@extends('layouts.app')

@section('title', 'Recherche : ' . $searchTerm)

@section('content')
<div class="container py-4" style="overflow-x:hidden;">
    <h1 class="h3 mb-3">Résultats pour « {{ $searchTerm }} »</h1>

    <x-event-search-form :action="route('search')" name="q" :value="$searchTerm" />

    @if($events->count())
        <p class="text-muted" role="status">{{ $events->total() }} événement(s) trouvé(s).</p>
        <div class="row g-3">
            @foreach($events as $event)
                <div class="col-12 col-sm-6 col-lg-4"><x-event-card :event="$event" /></div>
            @endforeach
        </div>
        <div class="mt-4">{{ $events->withQueryString()->links() }}</div>
    @else
        <div class="alert alert-info" role="status">Aucun événement ne correspond à votre recherche.</div>
        @if($categories->count())
            <p class="mb-2">Parcourir par catégorie :</p>
            <div class="d-flex flex-wrap gap-2">
                @foreach($categories as $category)
                    <a href="{{ route('categories.show', $category) }}" class="btn btn-outline-primary btn-sm" style="min-height:44px;display:inline-flex;align-items:center;">{{ $category->name }}</a>
                @endforeach
            </div>
        @endif
    @endif
</div>
@endsection
