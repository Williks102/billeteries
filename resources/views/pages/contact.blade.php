@extends('layouts.app')

@section('title', 'Nous contacter - ClicBillet CI')

@section('content')
<div class="container my-5">
    <!-- Hero Section -->
    <div class="row mb-5">
        <div class="col-12 text-center">
            <h1 class="display-4 fw-bold text-orange mb-4">Nous contacter</h1>
            <p class="lead">Une question ? Une suggestion ? Nous sommes là pour vous écouter</p>
        </div>
    </div>

    <div class="row">
        <!-- Formulaire de contact -->
        <div class="col-lg-8 mb-5">
            <div class="card border-0 shadow">
                <div class="card-body p-5">
                    <h3 class="mb-4"><i class="fas fa-envelope text-orange me-3"></i>Envoyez-nous un message</h3>
                    
                    <form action="{{ route('pages.contact.submit') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Nom complet *</label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                       id="name" name="name" value="{{ old('name') }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                       id="email" name="email" value="{{ old('email') }}" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="subject" class="form-label">Sujet *</label>
                            <select class="form-select @error('subject') is-invalid @enderror" id="subject" name="subject" required>
                                <option value="">Choisissez un sujet</option>
                                <option value="Question générale" {{ old('subject') == 'Question générale' ? 'selected' : '' }}>Question générale</option>
                                <option value="Problème technique" {{ old('subject') == 'Problème technique' ? 'selected' : '' }}>Problème technique</option>
                                <option value="Problème de paiement" {{ old('subject') == 'Problème de paiement' ? 'selected' : '' }}>Problème de paiement</option>
                                <option value="Demande de remboursement" {{ old('subject') == 'Demande de remboursement' ? 'selected' : '' }}>Demande de remboursement</option>
                                <option value="Devenir partenaire" {{ old('subject') == 'Devenir partenaire' ? 'selected' : '' }}>Devenir partenaire</option>
                                <option value="Autre" {{ old('subject') == 'Autre' ? 'selected' : '' }}>Autre</option>
                            </select>
                            @error('subject')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-4">
                            <label for="message" class="form-label">Message *</label>
                            <textarea class="form-control @error('message') is-invalid @enderror" 
                                      id="message" name="message" rows="6" 
                                      placeholder="Décrivez votre demande en détail..." required>{{ old('message') }}</textarea>
                            @error('message')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <button type="submit" class="btn btn-orange btn-lg">
                            <i class="fas fa-paper-plane me-2"></i>Envoyer le message
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Informations de contact -->
        <div class="col-lg-4">
            <div class="card border-0 shadow mb-4">
                <div class="card-body p-4">
                    <h5 class="mb-4"><i class="fas fa-info-circle text-orange me-2"></i>Informations de contact</h5>
                    
                    <div class="mb-3">
                        <h6><i class="fas fa-envelope text-orange me-2"></i>Email</h6>
                        <p class="mb-0">contact@clicbillet.com</p>
                        <small class="text-muted">Réponse dans l'heure</small>
                    </div>
                    
                    <div class="mb-3">
                        <h6><i class="fas fa-phone text-orange me-2"></i>Téléphone</h6>
                        <p class="mb-0">+225 07 02 49 02 77</p>
                        <small class="text-muted">Lun-Ven 8h-18h</small>
                    </div>
                    
                    <div class="mb-3">
                        <h6><i class="fas fa-map-marker-alt text-orange me-2"></i>Adresse</h6>
                        <p class="mb-0">Abidjan, Cocody<br>Côte d'Ivoire</p>
                    </div>
                    
                    <div class="mb-3">
                        <h6><i class="fas fa-clock text-orange me-2"></i>Horaires support</h6>
                        <p class="mb-0">Lundi - Vendredi : 8h - 18h<br>
                        Samedi : 9h - 15h<br>
                        Dimanche : Fermé</p>
                    </div>
                </div>
            </div>
            
            <!-- Liens rapides -->
            <div class="card border-0 shadow">
                <div class="card-body p-4">
                    <h5 class="mb-4"><i class="fas fa-link text-orange me-2"></i>Liens utiles</h5>
                    
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <a href="{{ route('pages.faq') }}" class="text-decoration-none">
                                <i class="fas fa-question-circle me-2"></i>FAQ
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="{{ route('pages.how-it-works') }}" class="text-decoration-none">
                                <i class="fas fa-info me-2"></i>Comment ça marche
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="{{ route('pages.support') }}" class="text-decoration-none">
                                <i class="fas fa-life-ring me-2"></i>Support technique
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="{{ route('pages.terms') }}" class="text-decoration-none">
                                <i class="fas fa-file-contract me-2"></i>Conditions d'utilisation
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- FAQ rapide -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="bg-light p-5 rounded">
                <h3 class="text-center mb-4">Questions fréquentes</h3>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="d-flex">
                            <i class="fas fa-question-circle text-orange me-3 mt-1"></i>
                            <div>
                                <h6>Comment modifier ma commande ?</h6>
                                <p class="small text-muted">Les modifications sont possibles jusqu'à 24h avant l'événement.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="d-flex">
                            <i class="fas fa-question-circle text-orange me-3 mt-1"></i>
                            <div>
                                <h6>Puis-je annuler ma commande ?</h6>
                                <p class="small text-muted">Selon les conditions de l'organisateur, contactez-nous.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="d-flex">
                            <i class="fas fa-question-circle text-orange me-3 mt-1"></i>
                            <div>
                                <h6>Je n'ai pas reçu mes billets</h6>
                                <p class="small text-muted">Vérifiez vos spams ou contactez-nous avec votre n° de commande.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="d-flex">
                            <i class="fas fa-question-circle text-orange me-3 mt-1"></i>
                            <div>
                                <h6>Comment devenir organisateur ?</h6>
                                <p class="small text-muted">Inscrivez-vous avec un compte promoteur sur notre plateforme.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection