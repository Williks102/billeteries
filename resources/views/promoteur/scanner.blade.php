
{{-- =============================================== --}}
@extends('layouts.promoteur')

@section('title', 'Scanner de Billets - ClicBillet CI')

@section('content')
<div class="scanner-page">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="fw-bold mb-1">
                <i class="fas fa-qrcode me-3"></i>
                Scanner de Billets
            </h1>
            <p class="text-muted">Scannez ou saisissez le code des billets pour validation</p>
        </div>
        <div class="scanner-stats">
            <span id="scan-count" class="badge bg-success fs-6 me-2">0 scannés aujourd'hui</span>
            <button class="btn btn-outline-primary" onclick="showStats()">
                <i class="fas fa-chart-bar me-2"></i>Statistiques
            </button>
        </div>
    </div>

    <!-- Instructions -->
    <div class="card mb-4 border-info">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h5 class="fw-bold mb-2 text-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Comment utiliser le scanner
                    </h5>
                    <ul class="mb-0">
                        <li>Scannez le QR code avec votre appareil photo</li>
                        <li>Ou saisissez manuellement le code du billet (format: TKT-XXXXXXXX)</li>
                        <li>Le système vérifiera automatiquement la validité</li>
                        <li>⚠️ Les billets utilisés ne peuvent pas être re-scannés</li>
                    </ul>
                </div>
                <div class="col-md-4 text-center">
                    <i class="fas fa-mobile-alt fa-4x text-info"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Scanner principal -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-scan me-2"></i>
                        Scanner Principal
                    </h5>
                </div>
                <div class="card-body">
                    <form id="scannerForm">
                        @csrf
                        <div class="row">
                            <div class="col-md-9 mb-3">
                                <label for="ticketCode" class="form-label fw-bold">
                                    <i class="fas fa-ticket-alt me-2"></i>
                                    Code du Billet
                                </label>
                                <input 
                                    type="text" 
                                    id="ticketCode" 
                                    name="ticket_code" 
                                    class="form-control form-control-lg" 
                                    placeholder="TKT-XXXXXXXX ou scannez le QR code"
                                    autocomplete="off"
                                    autofocus
                                    required
                                    style="font-family: 'Courier New', monospace; letter-spacing: 1px;"
                                >
                                <small class="form-text text-muted">
                                    Saisissez le code ou utilisez un scanner QR
                                </small>
                            </div>
                            <div class="col-md-3 mb-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-success btn-lg w-100" id="scanBtn">
                                    <i class="fas fa-search me-2"></i>
                                    Scanner
                                </button>
                            </div>
                        </div>
                    </form>
                    
                    <!-- Résultat du scan -->
                    <div id="scanResult" class="mt-4" style="display: none;"></div>
                    
                    <!-- Camera Scanner (optionnel) -->
                    <div class="mt-4">
                        <button type="button" class="btn btn-outline-info" onclick="toggleCamera()">
                            <i class="fas fa-camera me-2"></i>
                            Activer la caméra QR
                        </button>
                        <div id="cameraContainer" style="display: none;" class="mt-3">
                            <video id="cameraFeed" width="100%" height="300" style="border-radius: 10px;"></video>
                            <canvas id="cameraCanvas" style="display: none;"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Historique des scans -->
        <div class="col-lg-4">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-history me-2"></i>
                        Historique des Scans
                    </h5>
                </div>
                <div class="card-body">
                    <div id="scanHistory" class="scan-history">
                        <p class="text-muted text-center">Aucun scan effectué</p>
                    </div>
                </div>
            </div>
            
            <!-- Statistiques rapides -->
            <div class="card shadow mt-4">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-chart-pie me-2"></i>
                        Statistiques Rapides
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <h4 id="validScans" class="text-success">0</h4>
                            <small>Valides</small>
                        </div>
                        <div class="col-6">
                            <h4 id="invalidScans" class="text-danger">0</h4>
                            <small>Refusés</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
    .scanner-page .card {
        border: none;
        border-radius: 15px;
    }
    
    .scan-history {
        max-height: 400px;
        overflow-y: auto;
    }
    
    .scan-item {
        border-bottom: 1px solid #dee2e6;
        padding: 10px 0;
    }
    
    .scan-item:last-child {
        border-bottom: none;
    }
    
    .scan-valid {
        border-left: 4px solid #28a745;
        padding-left: 10px;
        background: #f8fff9;
    }
    
    .scan-invalid {
        border-left: 4px solid #dc3545;
        padding-left: 10px;
        background: #fff8f8;
    }
    
    .scan-used {
        border-left: 4px solid #ffc107;
        padding-left: 10px;
        background: #fffdf5;
    }
    
    #ticketCode {
        transition: all 0.3s ease;
    }
    
    #ticketCode:focus {
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        border-color: #80bdff;
    }
    
    .scanner-stats .badge {
        font-size: 0.9rem;
    }
</style>
@endpush

@push('scripts')
<script>
let scanCount = 0;
let validScans = 0;
let invalidScans = 0;

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('scannerForm');
    const ticketCodeInput = document.getElementById('ticketCode');
    const scanBtn = document.getElementById('scanBtn');
    const scanResult = document.getElementById('scanResult');
    
    // Auto-focus sur le champ
    ticketCodeInput.focus();
    
    // Soumission du formulaire
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        scanTicket();
    });
    
    // Auto-submit après saisie complète du code
    ticketCodeInput.addEventListener('input', function(e) {
        let value = e.target.value.toUpperCase();
        e.target.value = value;
        
        // Auto-scan si format complet (TKT-XXXXXXXX)
        if (value.match(/^TKT-[A-Z0-9]{8}$/)) {
            setTimeout(() => scanTicket(), 500);
        }
    });
});

function scanTicket() {
    const ticketCodeInput = document.getElementById('ticketCode');
    const ticketCode = ticketCodeInput.value.trim().toUpperCase();
    const scanBtn = document.getElementById('scanBtn');

    if (!ticketCode) {
        showError('Veuillez saisir un code de billet');
        return;
    }

    // Désactiver le bouton pendant le scan
    scanBtn.disabled = true;
    scanBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Scanning...';

    console.log('Scanning ticket:', ticketCode); // Debug

    // CORRECTION : Utiliser la route Laravel correcte
    fetch('{{ route("promoteur.scanner.verify") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json' // Ajout pour s'assurer d'obtenir du JSON
        },
        body: JSON.stringify({ ticket_code: ticketCode })
    })
    .then(response => {
        console.log('Response status:', response.status); // Debug
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data); // Debug
        
        if (data.success) {
            showSuccess(data);
            validScans++;
            addToHistory(data.ticket, 'valid');
        } else {
            showError(data.message || data.error, data.ticket);
            invalidScans++;
            addToHistory(data.ticket || { ticket_code: ticketCode }, 'invalid');
        }
        
        updateStats();
        clearInput();
    })
    .catch(error => {
        console.error('Erreur détaillée:', error);
        
        // Messages d'erreur plus spécifiques
        let errorMessage = 'Erreur de connexion';
        
        if (error.message.includes('Failed to fetch')) {
            errorMessage = 'Impossible de contacter le serveur. Vérifiez votre connexion internet.';
        } else if (error.message.includes('HTTP error! status: 419')) {
            errorMessage = 'Session expirée. Veuillez recharger la page.';
        } else if (error.message.includes('HTTP error! status: 403')) {
            errorMessage = 'Accès non autorisé. Vérifiez vos permissions.';
        } else if (error.message.includes('HTTP error! status: 500')) {
            errorMessage = 'Erreur serveur. Contactez l\'administrateur.';
        }
        
        showError(errorMessage);
        invalidScans++;
        updateStats();
    })
    .finally(() => {
        // Re-enable button
        scanBtn.disabled = false;
        scanBtn.innerHTML = '<i class="fas fa-search me-2"></i>Scanner';
    });
}

function showSuccess(data) {
    const scanResult = document.getElementById('scanResult');
    const ticket = data.ticket;
    
    scanResult.innerHTML = `
        <div class="alert alert-success alert-dismissible fade show">
            <div class="d-flex align-items-center">
                <i class="fas fa-check-circle fa-2x me-3"></i>
                <div class="flex-grow-1">
                    <h5 class="alert-heading">✅ Billet Valide Scanné !</h5>
                    <p class="mb-1"><strong>${ticket.event.title}</strong></p>
                    <p class="mb-1">Type: ${ticket.ticket_type} • Porteur: ${ticket.holder.name}</p>
                    <small>Scanné le ${new Date(data.scanned_at).toLocaleString('fr-FR')}</small>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    scanResult.style.display = 'block';
    
    // Sound effect (optionnel)
    playSuccessSound();
}

function showError(message, ticket = null) {
    const scanResult = document.getElementById('scanResult');
    
    let extraInfo = '';
    if (ticket && ticket.status === 'used') {
        extraInfo = `<p class="mb-1">⚠️ Billet déjà utilisé</p>`;
        if (ticket.used_at) {
            extraInfo += `<small>Utilisé le ${new Date(ticket.used_at).toLocaleString('fr-FR')}</small>`;
        }
    } else if (ticket) {
        extraInfo = `<p class="mb-1">${ticket.event ? ticket.event.title : ''}</p>`;
    }
    
    scanResult.innerHTML = `
        <div class="alert alert-danger alert-dismissible fade show">
            <div class="d-flex align-items-center">
                <i class="fas fa-times-circle fa-2x me-3"></i>
                <div class="flex-grow-1">
                    <h5 class="alert-heading">❌ ${message}</h5>
                    ${extraInfo}
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    scanResult.style.display = 'block';
    
    // Sound effect (optionnel)  
    playErrorSound();
}

function addToHistory(ticket, status) {
    const scanHistory = document.getElementById('scanHistory');
    
    // Supprimer le message vide
    if (scanHistory.querySelector('.text-muted')) {
        scanHistory.innerHTML = '';
    }
    
    const statusClass = status === 'valid' ? 'scan-valid' : 
                       (ticket && ticket.status === 'used') ? 'scan-used' : 'scan-invalid';
    
    const statusIcon = status === 'valid' ? '✅' : 
                      (ticket && ticket.status === 'used') ? '⚠️' : '❌';
    
    const historyItem = document.createElement('div');
    historyItem.className = `scan-item ${statusClass}`;
    historyItem.innerHTML = `
        <div class="d-flex justify-content-between align-items-start">
            <div class="flex-grow-1">
                <strong>${statusIcon} ${ticket.ticket_code || 'Code inconnu'}</strong>
                <br>
                <small class="text-muted">${ticket.event ? ticket.event.title : 'Événement inconnu'}</small>
                <br>
                <small class="text-muted">${new Date().toLocaleTimeString('fr-FR')}</small>
            </div>
        </div>
    `;
    
    // Ajouter en haut de l'historique
    scanHistory.insertBefore(historyItem, scanHistory.firstChild);
    
    // Limiter à 10 éléments
    const items = scanHistory.querySelectorAll('.scan-item');
    if (items.length > 10) {
        items[items.length - 1].remove();
    }
}

function updateStats() {
    scanCount++;
    document.getElementById('scan-count').textContent = `${scanCount} scannés aujourd'hui`;
    document.getElementById('validScans').textContent = validScans;
    document.getElementById('invalidScans').textContent = invalidScans;
}

function clearInput() {
    const ticketCodeInput = document.getElementById('ticketCode');
    ticketCodeInput.value = '';
    ticketCodeInput.focus();
}

function playSuccessSound() {
    // Optionnel: jouer un son de succès
    try {
        const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmcYAz+G1fTSeygCKnzM6t2QQg...');
        audio.play();
    } catch (e) {
        // Ignorer si audio non supporté
    }
}

function playErrorSound() {
    // Optionnel: jouer un son d'erreur
    try {
        const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmcYAz+G1fTSeygCKnzM6t2QQg...');
        audio.play();
    } catch (e) {
        // Ignorer si audio non supporté
    }
}

function toggleCamera() {
    const container = document.getElementById('cameraContainer');
    const video = document.getElementById('cameraFeed');
    
    if (container.style.display === 'none') {
        // Activer la caméra
        container.style.display = 'block';
        startCamera();
    } else {
        // Désactiver la caméra
        container.style.display = 'none';
        stopCamera();
    }
}

function startCamera() {
    navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
        .then(stream => {
            const video = document.getElementById('cameraFeed');
            video.srcObject = stream;
            video.play();
            
            // TODO: Intégrer une bibliothèque de scan QR comme jsQR
            // https://github.com/cozmo/jsQR
        })
        .catch(err => {
            console.error('Erreur caméra:', err);
            alert('Impossible d\'accéder à la caméra');
        });
}

function stopCamera() {
    const video = document.getElementById('cameraFeed');
    if (video.srcObject) {
        video.srcObject.getTracks().forEach(track => track.stop());
    }
}

function showStats() {
    // Afficher un modal avec des statistiques détaillées
    alert(`Statistiques de scan:\n\nTotal: ${scanCount}\nValides: ${validScans}\nRefusés: ${invalidScans}\nTaux de succès: ${scanCount > 0 ? Math.round((validScans/scanCount)*100) : 0}%`);
}

// Raccourcis clavier
document.addEventListener('keydown', function(e) {
    // F5 = Focus sur input
    if (e.key === 'F5') {
        e.preventDefault();
        document.getElementById('ticketCode').focus();
    }
    
    // Escape = Clear input
    if (e.key === 'Escape') {
        clearInput();
    }
});
</script>
@endpush