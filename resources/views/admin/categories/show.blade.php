@extends('layouts.admin')

@section('title', 'Catégorie ' . $category->name)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.categories.index') }}">Catégories</a></li>
    <li class="breadcrumb-item active">{{ $category->name }}</li>
@endsection

@section('content')
<div class="admin-page">
    <x-admin.page-header :title="$category->name" :subtitle="$category->description" :back="route('admin.categories.index')">
        <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-primary">
            <i class="fas fa-edit" aria-hidden="true"></i> Modifier
        </a>
    </x-admin.page-header>

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4"><x-admin.stat-card label="Événements" :value="$stats['total_events']" /></div>
        <div class="col-6 col-md-4"><x-admin.stat-card label="Publiés" :value="$stats['published_events']" /></div>
        <div class="col-6 col-md-4"><x-admin.stat-card label="Publiés à venir" :value="$stats['upcoming_events']" /></div>
    </div>

    <h2 class="h5 mb-3">Événements de la catégorie</h2>
    @if($category->events->isEmpty())
        <div class="alert alert-info">Aucun événement dans cette catégorie.</div>
    @else
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <caption class="visually-hidden">Événements de la catégorie {{ $category->name }}</caption>
                    <thead>
                        <tr>
                            <th scope="col">Événement</th>
                            <th scope="col" class="d-none d-md-table-cell">Promoteur</th>
                            <th scope="col">Date</th>
                            <th scope="col">Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($category->events as $event)
                            <tr>
                                <td><a href="{{ route('admin.events.show', $event) }}">{{ $event->title }}</a></td>
                                <td class="d-none d-md-table-cell">{{ $event->promoter?->name ?? '-' }}</td>
                                <td>{{ $event->event_date?->format('d/m/Y') ?? '-' }}</td>
                                <td><span class="badge bg-secondary">{{ $event->status }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
