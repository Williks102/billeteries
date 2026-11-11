@extends('layouts.admin')

@section('title', 'Modifier la catégorie ' . $category->name)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.categories.index') }}">Catégories</a></li>
    <li class="breadcrumb-item active">{{ $category->name }}</li>
@endsection

@section('content')
<div class="admin-page">
    <x-admin.page-header :title="'Modifier : ' . $category->name" :back="route('admin.categories.index')" />
    <div class="row"><div class="col-lg-8"><div class="card"><div class="card-body">
        <x-admin.category-form :action="route('admin.categories.update', $category)" :category="$category" />
    </div></div></div></div>
</div>
@endsection
