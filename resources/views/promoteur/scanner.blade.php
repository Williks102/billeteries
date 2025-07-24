{{-- resources/views/promoteur/scanner.blade.php - VERSION CORRIGÉE --}}
@extends('layouts.promoteur')

@push('styles')
<style>
    .scanner-container {
        max-width: 800px;
        margin: 0 auto;
    }
    
    .scanner-card {
        background: white;
        border-radius: 15px;
        padding: 2rem;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        border-left: 4px solid #FF6B35;
        margin-bottom: 2rem;
    }
    
    .scanner-input {
        border: 3px solid #e9ecef;
        border-radius: 10px;
        padding: 1rem;
        font-size: 1.25rem;
        text-align: center;
        transition: all 0.3s ease;
    }
    
    .scanner-input:focus {
        border-color: #FF6B35;
        box-shadow: 0 0 0 0.2rem rgba(255, 107, 53, 0.25);
    }
    
    .btn-scan {
        background: linear-gradient(135deg, #FF6B35, #E55A2B);
        border: none;
        color: white;
        padding: 1rem 2rem;
        font-size: 1.1rem;
        border-radius: 10px;
        width: 100%;
    }
    
    .btn-scan:hover {
        background: linear-gradient(135deg, #E55A2B, #D4491F);
        color: white;
    }
    
    .result-card {
        border-radius: 15px;
        padding: 2rem;
        margin-top: 2rem;
        display: none;
    }
    
    .result-success {
        background: linear-gradient(135deg, #d4edda, #c3e6cb);
        border: 2px solid #28a745;
    }
    
    .result-error {
        background: linear-gradient(135deg, #f8d7da, #f1b0b7);
        border: 2px solid #dc3545;
    }
    
    .qr-instructions {
        background: linear-gradient(135deg, #e2e3e5, #d6d8db);
        border-radius: 10px;
        padding: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .loading-spinner {
        display: none;
        text-align: center;
        padding: 1rem;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="scanner-container">
        <!-- En-tête -->
        <div class="text-center mb-4">
            <h1 class="h2" style="color: #FF6B35;">
                <i class="fas fa-qrcode me-3"></i>
                Scanner de Billets
            </h1>
            <p class="text-muted">Scannez ou saisissez le code des billets pour validation</p>
        </div>

        <!-- Instructions -->
        <div class="qr-instructions">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h5 class="fw-bold mb-2">
                        <i class="fas fa-info-circle me-2"></i>
                        Comment utiliser le scanner
                    </h5>
                    <ul class="mb-0 small">
                        <li>Scannez le QR code avec votre appareil photo</li>
                        <li>Ou saisissez manuellement le code du billet</li>
                        <li>Le système vérifiera automatiquement la validité</li>
                        <li>Les billets utilisés ne peuvent pas être re-scannés</li>
                    </ul>
                </div>
                <div class="col-md-4 text-center">
                    <i class="fas fa-mobile-alt fa-4x text-muted"></i>
                </div>
            </div>
        </div>

        <!-- Scanner principal -->
        <div class="scanner-card">
            <form id="scannerForm">
                @csrf
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label for="ticketCode" class="form-label fw-bold">
                            <i class="fas fa-ticket-alt me-2"></i>
                            Code du Billet
                        </label>
                        <input 
                            type="text" 
                            id="ticketCode" 
                            name="ticket_code" 
                            class="form-control scanner-input" 
                            placeholder="TKT-XXXXXXXX"
                            autocomplete="off"
                            autofocus
                            required
                        >
                        <small class="text-muted">Scannez le QR code ou saisissez le code manuellement</small>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-scan">
                            <i class="fas fa-search me-2"></i>
                            Vérifier
                        </button>
                    </div>
                </div>
            </form>

            <!-- Spinner de chargement -->
            <div id="loadingSpinner" class="loading-spinner">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Vérification en cours...</span>
                </div>
                <p class="mt-2 text-muted">Vérification du billet...</p>
            </div>
        </div>

        <!-- Résultat de la vérification -->
        <div id="resultCard" class="result-card">
            <div id="resultContent"></div>
        </div>

        <!-- Historique des scans récents -->
        <div class="scanner-card">
            <h5 class="fw-bold mb-3">
                <i class="fas fa-history me-2"></i>
                Scans Récents
            </h5>
            <div id="recentScans">
                <p class="text-muted text-center">Aucun scan récent</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('scannerForm');
    const ticketCodeInput = document.getElementById('ticketCode');
    const loadingSpinner = document.getElementById('loadingSpinner');
    const resultCard = document.getElementById('resultCard');
    const resultContent = document.getElementById('resultContent');
    const recentScans = document.getElementById('recentScans');
    
    // Array pour stocker les scans récents
    let recentScansData = [];
    
    // Soumission du formulaire
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const ticketCode = ticketCodeInput.value.trim();
        if (!ticketCode) {
            showError('Veuillez saisir un code de billet');
            return;
        }
        
        verifyTicket(ticketCode);
    });
    
    // Auto-submit quand on colle ou scanne un code
    ticketCodeInput.addEventListener('input', function(e) {
        const value = e.target.value.trim();
        // Si ça ressemble à un code complet (TKT-XXXXXXXX), vérifier automatiquement
        if (value.match(/^TKT-[A-Z0-9]{8}$/i)) {
            setTimeout(() => verifyTicket(value), 100);
        }
    });
    
    function verifyTicket(ticketCode) {
        // Afficher le spinner
        loadingSpinner.style.display = 'block';
        resultCard.style.display = 'none';
        
        // Faire la requête AJAX
        fetch('{{ route("promoteur.scanner.verify") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') || '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                ticket_code: ticketCode
            })
        })
        .then(response => response.json())
        .then(data => {
            loadingSpinner.style.display = 'none';
            
            if (data.success) {
                showSuccess(data.message, data.ticket);
                addToRecentScans(ticketCode, 'success', data.ticket);
            } else {
                showError(data.message, data.ticket);
                addToRecentScans(ticketCode, 'error', data.ticket);
            }
            
            // Vider le champ pour le prochain scan
            ticketCodeInput.value = '';
            ticketCodeInput.focus();
        })
        .catch(error => {
            loadingSpinner.style.display = 'none';
            console.error('Erreur:', error);
            showError('Erreur de connexion. Veuillez réessayer.');
            ticketCodeInput.focus();
        });
    }
    
    function showSuccess(message, ticket) {
        resultCard.className = 'result-card result-success';
        resultCard.style.display = 'block';
        
        let ticketInfo = '';
        if (ticket) {
            ticketInfo = `
                <div class="mt-3">
                    <h6>Informations du billet :</h6>
                    <ul class="mb-0">
                        <li><strong>Événement :</strong> ${ticket.event_title || 'N/A'}</li>
                        <li><strong>Type :</strong> ${ticket.ticket_type || 'N/A'}</li>
                        <li><strong>Lieu :</strong> ${ticket.venue || 'N/A'}</li>
                        <li><strong>Date :</strong> ${ticket.event_date || 'N/A'} à ${ticket.event_time || 'N/A'}</li>
                    </ul>
                </div>
            `;
        }
        
        resultContent.innerHTML = `
            <div class="text-center">
                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                <h4 class="text-success">Billet Valide !</h4>
                <p class="mb-0">${message}</p>
                ${ticketInfo}
            </div>
        `;
    }
    
    function showError(message, ticket) {
        resultCard.className = 'result-card result-error';
        resultCard.style.display = 'block';
        
        let ticketInfo = '';
        if (ticket) {
            ticketInfo = `
                <div class="mt-3">
                    <h6>Informations du billet :</h6>
                    <ul class="mb-0">
                        <li><strong>Code :</strong> ${ticket.ticket_code || 'N/A'}</li>
                        <li><strong>Statut :</strong> ${ticket.status || 'N/A'}</li>
                        <li><strong>Événement :</strong> ${ticket.event_title || 'N/A'}</li>
                    </ul>
                </div>
            `;
        }
        
        resultContent.innerHTML = `
            <div class="text-center">
                <i class="fas fa-times-circle fa-3x text-danger mb-3"></i>
                <h4 class="text-danger">Billet Non Valide</h4>
                <p class="mb-0">${message}</p>
                ${ticketInfo}
            </div>
        `;
    }
    
    function addToRecentScans(code, status, ticket) {
        const scan = {
            code: code,
            status: status,
            timestamp: new Date().toLocaleString('fr-FR'),
            ticket: ticket
        };
        
        recentScansData.unshift(scan);
        
        // Garder seulement les 10 derniers
        if (recentScansData.length > 10) {
            recentScansData = recentScansData.slice(0, 10);
        }
        
        updateRecentScansDisplay();
    }
    
    function updateRecentScansDisplay() {
        if (recentScansData.length === 0) {
            recentScans.innerHTML = '<p class="text-muted text-center">Aucun scan récent</p>';
            return;
        }
        
        let html = '<div class="list-group">';
        recentScansData.forEach(scan => {
            const statusIcon = scan.status === 'success' ? 'fa-check-circle text-success' : 'fa-times-circle text-danger';
            const statusText = scan.status === 'success' ? 'Valide' : 'Invalide';
            
            html += `
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <code class="fw-bold">${scan.code}</code>
                        <br>
                        <small class="text-muted">${scan.timestamp}</small>
                    </div>
                    <div class="text-end">
                        <i class="fas ${statusIcon} me-2"></i>
                        <span class="badge bg-${scan.status === 'success' ? 'success' : 'danger'}">${statusText}</span>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        
        recentScans.innerHTML = html;
    }
    
    // Focus automatique sur le champ
    ticketCodeInput.focus();
});
</script>
@endpush