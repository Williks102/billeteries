@extends('layouts.admin')

@section('title', 'Catégories')

@section('breadcrumb')
    <li class="breadcrumb-item active">Catégories</li>
@endsection

@section('content')
<div class="admin-page">
    <x-admin.page-header title="Catégories" :subtitle="$categories->total() . ' catégorie(s)'">
        <a href="{{ route('admin.categories.create') }}" class="btn btn-primary">
            <i class="fas fa-plus" aria-hidden="true"></i> Nouvelle catégorie
        </a>
    </x-admin.page-header>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3"><x-admin.stat-card label="Catégories" :value="$stats['total']" /></div>
        <div class="col-6 col-lg-3"><x-admin.stat-card label="Avec événements" :value="$stats['with_events']" /></div>
        <div class="col-6 col-lg-3"><x-admin.stat-card label="Vides" :value="$stats['empty_categories']" /></div>
        <div class="col-6 col-lg-3"><x-admin.stat-card label="Événements au total" :value="$stats['total_events']" /></div>
    </div>

    <form method="GET" action="{{ route('admin.categories.index') }}" class="row g-2 align-items-end mb-4">
        <div class="col-12 col-md-5">
            <label for="search" class="form-label">Recherche</label>
            <input type="search" id="search" name="search" class="form-control" value="{{ request('search') }}">
        </div>
        <div class="col-12 col-md-4">
            <label for="status" class="form-label">Filtre</label>
            <select id="status" name="status" class="form-select">
                <option value="">Toutes</option>
                <option value="active" @selected(request('status') === 'active')>Avec événements publiés à venir</option>
                <option value="empty" @selected(request('status') === 'empty')>Sans événement</option>
            </select>
        </div>
        <div class="col-12 col-md-auto">
            <button type="submit" class="btn btn-primary w-100">Filtrer</button>
        </div>
    </form>

    @if($categories->isEmpty())
        <div class="alert alert-info">Aucune catégorie ne correspond.</div>
    @else
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <caption class="visually-hidden">Liste des catégories</caption>
                    <thead>
                        <tr>
                            <th scope="col">Nom</th>
                            <th scope="col" class="d-none d-md-table-cell">Slug</th>
                            <th scope="col" class="text-end">Événements</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($categories as $category)
                            <tr>
                                <td>
                                    @if($category->icon)<i class="{{ $category->icon }} me-1" aria-hidden="true"></i>@endif
                                    <a href="{{ route('admin.categories.show', $category) }}">{{ $category->name }}</a>
                                </td>
                                <td class="d-none d-md-table-cell text-muted">{{ $category->slug }}</td>
                                <td class="text-end">{{ $category->events_count }}</td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-outline-primary btn-sm" aria-label="Modifier {{ $category->name }}">
                                            <i class="fas fa-edit" aria-hidden="true"></i>
                                        </a>
                                        @if($category->events_count === 0)
                                            <form method="POST" action="{{ route('admin.categories.destroy', $category) }}"
                                                  onsubmit="return confirm('Supprimer cette catégorie ?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger btn-sm" aria-label="Supprimer {{ $category->name }}">
                                                    <i class="fas fa-trash" aria-hidden="true"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-3">{{ $categories->appends(request()->query())->links('pagination::bootstrap-5') }}</div>
    @endif
</div>
@endsection
