@extends('layouts.app')

@section('title', $category->name . ' - Événements')

@section('content')
<div class="container py-4" style="overflow-x:hidden;">
    <h1 class="h3 mb-1">{{ $category->name }}</h1>
    <p class="text-muted mb-4">
        {{ $categoryStats['upcoming_events'] }} événement(s) à venir
        @if($categoryStats['this_month'] > 0) dont {{ $categoryStats['this_month'] }} ce mois-ci @endif
    </p>

    <x-event-search-form :action="route('categories.show', $category)" name="search" :value="request('search', '')" label="Rechercher dans cette catégorie" />

    @if($categories->count() > 1)
        <nav aria-label="Catégories" class="mb-4 d-flex flex-wrap gap-2">
            @foreach($categories as $other)
                <a href="{{ route('categories.show', $other) }}"
                   class="btn btn-sm {{ $other->id === $category->id ? 'btn-primary' : 'btn-outline-primary' }}"
                   style="min-height:44px;display:inline-flex;align-items:center;"
                   @if($other->id === $category->id) aria-current="page" @endif>{{ $other->name }}</a>
            @endforeach
        </nav>
    @endif

    @if($events->count())
        <div class="row g-3">
            @foreach($events as $event)
                <div class="col-12 col-sm-6 col-lg-4"><x-event-card :event="$event" /></div>
            @endforeach
        </div>
        <div class="mt-4">{{ $events->withQueryString()->links() }}</div>
    @else
        <div class="alert alert-info" role="status">Aucun événement à venir dans cette catégorie pour le moment.</div>
    @endif
</div>
@endsection
