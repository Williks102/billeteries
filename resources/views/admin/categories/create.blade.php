@extends('layouts.admin')

@section('title', 'Nouvelle catégorie')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.categories.index') }}">Catégories</a></li>
    <li class="breadcrumb-item active" aria-current="page">Nouvelle</li>
@endsection

@section('content')
<div class="admin-page">
    <x-admin.page-header title="Nouvelle catégorie" :back="route('admin.categories.index')" />
    <div class="row"><div class="col-lg-8"><div class="card"><div class="card-body">
        <x-admin.category-form :action="route('admin.categories.store')" />
    </div></div></div></div>
</div>
@endsection
