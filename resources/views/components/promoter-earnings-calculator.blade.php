<!-- resources/views/components/promoter-earnings-calculator.blade.php -->

<div class="earnings-calculator card border-success">
    <div class="card-header bg-gradient text-white" style="background: linear-gradient(135deg, #FF6B35 0%, #1a1a1a 100%);">
        <h6 class="mb-0">
            <i class="fas fa-calculator me-2"></i>
            💰 Calculateur de vos gains
        </h6>
    </div>
    <div class="card-body">
        <!-- Inputs pour calcul en temps réel -->
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label text-muted small">Prix du billet (FCFA)</label>
                <input type="number" class="form-control" id="ticket-price" value="5000" min="500" step="500">
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted small">Nombre de billets estimé</label>
                <input type="number" class="form-control" id="ticket-quantity" value="50" min="1" step="1">
            </div>
        </div>
        
        <!-- Résultats du calcul -->
        <div class="calculation-results">
            <div class="row">
                <div class="col-md-6">
                    <div class="metric-box text-center p-3 mb-3" style="background: #f8f9fa; border-radius: 10px;">
                        <div class="metric-value h4 mb-1 text-primary" id="total-revenue">250 000 FCFA</div>
                        <div class="metric-label text-muted small">Revenus bruts</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="metric-box text-center p-3 mb-3" style="background: #e8f5e8; border-radius: 10px;">
                        <div class="metric-value h4 mb-1 text-success" id="net-earnings">225 000 FCFA</div>
                        <div class="metric-label text-muted small">Vos gains nets</div>
                    </div>
                </div>
            </div>
            
            <!-- Détails de la répartition -->
            <div class="commission-breakdown">
                <h6 class="mb-3">📊 Répartition des revenus :</h6>
                
                <!-- Barre de progression visuelle -->
                <div class="progress mb-3" style="height: 25px; border-radius: 15px;">
                    <div class="progress-bar bg-success" role="progressbar" 
                         style="width: 90%" id="promoter-bar">
                        <span class="fw-bold">Vous : <span id="promoter-percentage">90%</span></span>
                    </div>
                    <div class="progress-bar bg-orange" role="progressbar" 
                         style="width: 10%" id="platform-bar">
                        <span class="fw-bold text-white">Plateforme : <span id="platform-percentage">10%</span></span>
                    </div>
                </div>
                
                <!-- Détails ligne par ligne -->
                <div class="breakdown-details">
                    <div class="d-flex justify-content-between mb-2">
                        <span>💰 Revenus bruts :</span>
                        <strong id="gross-amount">250 000 FCFA</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2 text-danger">
                        <span>📝 Commission plateforme (<span id="commission-rate">10%</span>) :</span>
                        <strong id="commission-amount">- 25 000 FCFA</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2 text-warning">
                        <span>⚙️ Frais techniques (<span id="platform-fees-display">500 FCFA/billet</span>) :</span>
                        <strong id="platform-fees">- 25 000 FCFA</strong>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="fw-bold text-success">💵 Vos gains nets :</span>
                        <strong class="text-success h5" id="final-earnings">200 000 FCFA</strong>
                    </div>
                </div>
                
                <!-- Projections mensuelles -->
                <div class="monthly-projection p-3 mt-3" style="background: linear-gradient(135deg, rgba(255, 107, 53, 0.1) 0%, rgba(26, 26, 26, 0.05) 100%); border-radius: 10px;">
                    <h6 class="text-primary mb-2">📈 Projections mensuelles :</h6>
                    <div class="row">
                        <div class="col-6">
                            <small class="text-muted">1 événement/mois :</small>
                            <div class="fw-bold" id="monthly-1-event">200 000 FCFA</div>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">2 événements/mois :</small>
                            <div class="fw-bold text-success" id="monthly-2-events">400 000 FCFA</div>
                        </div>
                    </div>
                </div>
                
                <!-- Tips pour maximiser les gains -->
                <div class="tips-section mt-3">
                    <div class="alert alert-info">
                        <strong>💡 Tips pour maximiser vos gains :</strong>
                        <ul class="mb-0 mt-2">
                            <li>🎯 Événements réguliers = revenus stables</li>
                            <li>📱 Promotion active = plus de ventes</li>
                            <li>⭐ Qualité événement = fidélisation public</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.earnings-calculator .progress-bar {
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    font-weight: 600;
}

.bg-orange {
    background-color: #FF6B35 !important;
}

.metric-box {
    transition: transform 0.2s ease;
}

.metric-box:hover {
    transform: scale(1.02);
}

.breakdown-details {
    font-size: 0.9rem;
}

.monthly-projection {
    border: 2px dashed #FF6B35;
}
</style>

<script src="{{ asset('js/components-promoter-earnings-calculator.js') }}" defer></script>

<?php
// ========== UTILISATION DANS UN FORMULAIRE PROMOTEUR ==========

// Exemple d'utilisation dans un formulaire de création d'événement :
?>

<!-- Dans resources/views/promoteur/events/create.blade.php -->
<div class="row">
    <div class="col-lg-8">
        <!-- Formulaire de création d'événement -->
        <form method="POST" action="{{ route('promoteur.events.store') }}">
            @csrf
            
            <div class="card">
                <div class="card-header">
                    <h5>📅 Créer un nouvel événement</h5>
                </div>
                <div class="card-body">
                    <!-- Champs du formulaire... -->
                    <div class="mb-3">
                        <label class="form-label">Titre de l'événement</label>
                        <input type="text" class="form-control" name="title" required>
                    </div>
                    
                    <!-- ... autres champs ... -->
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Créer l'événement
                    </button>
                </div>
            </div>
        </form>
    </div>
    
    <div class="col-lg-4">
        <!-- Widget calculateur de gains -->
        @include('components.promoter-earnings-calculator')
    </div>
</div>