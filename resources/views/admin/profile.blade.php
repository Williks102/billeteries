@extends('layouts.app')

@section('title', 'Mon profil - Admin')
@section('body-class', 'admin-page')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <!-- Sidebar Admin -->
        <div class="col-md-3 col-lg-2">
            <div class="admin-sidebar bg-dark text-white rounded p-3 sticky-top">
                <h5 class="mb-4">
                    <i class="fas fa-shield-alt text-orange me-2"></i>
                    Administration
                </h5>
                
                <nav class="nav flex-column">
                    <a class="nav-link text-light" href="{{ route('admin.dashboard') }}">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                    <a class="nav-link active text-white" href="{{ route('admin.profile') }}">
                        <i class="fas fa-user-cog me-2"></i>Mon profil
                    </a>
                    <!-- Autres liens... -->
                </nav>
            </div>
        </div>
        
        <!-- Contenu principal -->
        <div class="col-md-9 col-lg-10">
            <h2 class="mb-4">Mon profil administrateur</h2>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Informations personnelles</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Nom :</strong> {{ $user->name }}</p>
                            <p><strong>Email :</strong> {{ $user->email }}</p>
                            <p><strong>Rôle :</strong> 
                                <span class="badge bg-danger">Administrateur</span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Membre depuis :</strong> {{ $user->created_at->format('d/m/Y') }}</p>
                            <p><strong>Dernière connexion :</strong> {{ $user->updated_at->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection