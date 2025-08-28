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
                    
                    <!-- Camera Scanner AMÉLIORÉ -->
                    <div class="mt-4">
                        <div class="d-flex gap-2 align-items-center mb-3">
                            <button type="button" class="btn btn-outline-info" onclick="toggleCamera()">
                                <i class="fas fa-camera me-2"></i>
                                <span id="cameraButtonText">Activer la caméra QR</span>
                            </button>
                            
                            <button type="button" class="btn btn-info btn-sm" onclick="testCameraPermissions()" 
                                    title="Tester les permissions caméra">
                                <i class="fas fa-shield-alt"></i>
                            </button>
                            
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="showCameraHelp()" 
                                    title="Aide pour la caméra">
                                <i class="fas fa-question-circle"></i>
                            </button>
                        </div>
                        
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
<!-- Bibliothèque jsQR pour la détection des QR codes -->
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>

<script>
let scanCount = 0;
let validScans = 0;
let invalidScans = 0;

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('scannerForm');
    const ticketCodeInput = document.getElementById('ticketCode');
    
    // Focus automatique sur l'input
    ticketCodeInput.focus();
    
    // Traitement du formulaire
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        processTicket();
    });
    
    // Auto-submit si code complet (TKT-XXXXXXXX)
    ticketCodeInput.addEventListener('input', function(e) {
        const code = e.target.value.trim();
        if (code.length >= 12 && code.startsWith('TKT-')) {
            setTimeout(() => {
                processTicket();
            }, 500);
        }
    });
    
    // Nettoyage automatique du champ
    ticketCodeInput.addEventListener('focus', function() {
        this.select();
    });
});

function processTicket() {
    const ticketCode = document.getElementById('ticketCode').value.trim();
    const scanBtn = document.getElementById('scanBtn');
    
    if (!ticketCode) {
        alert('Veuillez saisir un code de billet');
        return;
    }
    
    // Disable button during request
    scanBtn.disabled = true;
    scanBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Vérification...';
    
    // AJAX request to verify ticket
    fetch('{{ route("promoteur.scanner.verify") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            ticket_code: ticketCode
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccess(data);
            validScans++;
            addToHistory(data.ticket, 'valid');
        } else {
            showError(data.error, data.ticket);
            addToHistory(data.ticket || { ticket_code: ticketCode }, 'invalid');
        }
        updateStats();
        clearInput();
    })
    .catch(error => {
        console.error('Erreur:', error);
        
        let errorMessage = 'Erreur de connexion. Vérifiez votre connexion internet.';
        if (error.message.includes('HTTP error! status: 419')) {
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

// ========== NOUVELLES FONCTIONS CAMÉRA AMÉLIORÉES ==========

function startCamera() {
    // Vérifier si getUserMedia est supporté
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        showCameraError('Votre navigateur ne supporte pas l\'accès à la caméra. Utilisez Chrome, Firefox ou Safari récent.');
        return;
    }

    // Vérifier si on est en HTTPS (requis pour la caméra)
    if (location.protocol !== 'https:' && location.hostname !== 'localhost') {
        showCameraError('L\'accès à la caméra nécessite une connexion HTTPS sécurisée.');
        return;
    }

    // Options pour la caméra (privilégier la caméra arrière)
    const constraints = {
        video: {
            facingMode: 'environment', // Caméra arrière préférée
            width: { ideal: 1280 },
            height: { ideal: 720 }
        }
    };

    // Afficher un indicateur de chargement
    const cameraContainer = document.getElementById('cameraContainer');
    cameraContainer.innerHTML = `
        <div class="text-center p-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Activation de la caméra...</span>
            </div>
            <p class="mt-2">Activation de la caméra en cours...</p>
            <p class="text-muted small">Autorisez l'accès à votre caméra si demandé</p>
        </div>
    `;

    navigator.mediaDevices.getUserMedia(constraints)
        .then(stream => {
            // Succès - afficher le flux vidéo
            cameraContainer.innerHTML = `
                <video id="cameraFeed" width="100%" height="300" style="border-radius: 10px;" autoplay playsinline></video>
                <canvas id="cameraCanvas" style="display: none;"></canvas>
                <div class="mt-2 text-center">
                    <button type="button" class="btn btn-danger btn-sm" onclick="stopCamera()">
                        <i class="fas fa-stop me-1"></i>Arrêter la caméra
                    </button>
                    <div class="alert alert-success mt-2" role="alert">
                        <i class="fas fa-search me-2"></i>Recherche automatique de codes QR en cours...
                    </div>
                </div>
            `;
            
            const video = document.getElementById('cameraFeed');
            video.srcObject = stream;
            
            // Commencer la détection QR une fois la vidéo prête
            video.onloadedmetadata = () => {
                startQRDetection();
            };
        })
        .catch(err => {
            console.error('Erreur caméra détaillée:', err);
            
            let errorMessage = 'Impossible d\'accéder à la caméra.';
            
            if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                errorMessage = 'Accès à la caméra refusé. Veuillez autoriser l\'accès dans votre navigateur.';
            } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
                errorMessage = 'Aucune caméra trouvée sur cet appareil.';
            } else if (err.name === 'NotReadableError' || err.name === 'TrackStartError') {
                errorMessage = 'La caméra est déjà utilisée par une autre application.';
            } else if (err.name === 'OverconstrainedError') {
                errorMessage = 'Les paramètres de caméra demandés ne sont pas supportés.';
            } else if (err.name === 'NotSupportedError') {
                errorMessage = 'Fonctionnalité caméra non supportée par votre navigateur.';
            }
            
            showCameraError(errorMessage, err);
        });
}

function showCameraError(message, error = null) {
    const cameraContainer = document.getElementById('cameraContainer');
    
    cameraContainer.innerHTML = `
        <div class="alert alert-danger">
            <div class="d-flex align-items-center">
                <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                <div class="flex-grow-1">
                    <h6 class="alert-heading mb-2">Problème d'accès à la caméra</h6>
                    <p class="mb-2">${message}</p>
                    <div class="mt-3">
                        <button type="button" class="btn btn-primary btn-sm me-2" onclick="retryCamera()">
                            <i class="fas fa-redo me-1"></i>Réessayer
                        </button>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="showCameraHelp()">
                            <i class="fas fa-question-circle me-1"></i>Aide
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Masquer le conteneur après l'erreur
    setTimeout(() => {
        document.getElementById('cameraContainer').style.display = 'none';
    }, 100);
}

function retryCamera() {
    const container = document.getElementById('cameraContainer');
    container.style.display = 'block';
    startCamera();
}

function showCameraHelp() {
    const helpModal = `
        <div class="modal fade" id="cameraHelpModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Aide pour l'accès à la caméra</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <h6><i class="fab fa-chrome text-warning me-2"></i>Chrome / Edge</h6>
                        <ul class="small mb-3">
                            <li>Cliquez sur l'icône de caméra dans la barre d'adresse</li>
                            <li>Sélectionnez "Toujours autoriser"</li>
                            <li>Rechargez la page</li>
                        </ul>
                        
                        <h6><i class="fab fa-firefox text-orange me-2"></i>Firefox</h6>
                        <ul class="small mb-3">
                            <li>Cliquez sur "Autoriser" quand demandé</li>
                            <li>Ou allez dans Paramètres > Vie privée > Permissions</li>
                        </ul>
                        
                        <h6><i class="fab fa-safari text-primary me-2"></i>Safari</h6>
                        <ul class="small mb-3">
                            <li>Allez dans Safari > Paramètres > Sites web</li>
                            <li>Autorisez l'accès à la caméra pour ce site</li>
                        </ul>
                        
                        <div class="alert alert-info small">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Important :</strong> Assurez-vous qu'aucune autre application n'utilise votre caméra.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                        <button type="button" class="btn btn-primary" onclick="retryCamera(); bootstrap.Modal.getInstance(document.getElementById('cameraHelpModal')).hide();">
                            Réessayer maintenant
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Ajouter le modal au DOM s'il n'existe pas
    if (!document.getElementById('cameraHelpModal')) {
        document.body.insertAdjacentHTML('beforeend', helpModal);
    }
    
    // Afficher le modal
    new bootstrap.Modal(document.getElementById('cameraHelpModal')).show();
}

function toggleCamera() {
    const container = document.getElementById('cameraContainer');
    const button = event.target.closest('button');
    
    if (container.style.display === 'none' || container.style.display === '') {
        // Activer la caméra
        container.style.display = 'block';
        button.innerHTML = '<i class="fas fa-camera me-2"></i>Désactiver la caméra';
        button.classList.remove('btn-outline-info');
        button.classList.add('btn-outline-danger');
        startCamera();
    } else {
        // Désactiver la caméra
        container.style.display = 'none';
        button.innerHTML = '<i class="fas fa-camera me-2"></i>Activer la caméra QR';
        button.classList.remove('btn-outline-danger');
        button.classList.add('btn-outline-info');
        stopCamera();
    }
}

function stopCamera() {
    const video = document.getElementById('cameraFeed');
    if (video && video.srcObject) {
        // Arrêter tous les tracks de la caméra
        video.srcObject.getTracks().forEach(track => {
            track.stop();
            console.log('Camera track stopped:', track.kind);
        });
        video.srcObject = null;
    }
    
    // Arrêter la détection QR
    if (window.qrDetectionInterval) {
        clearInterval(window.qrDetectionInterval);
        window.qrDetectionInterval = null;
    }
    
    console.log('Caméra arrêtée avec succès');
}

// Fonction de détection QR (nécessite jsQR)
function startQRDetection() {
    const video = document.getElementById('cameraFeed');
    const canvas = document.getElementById('cameraCanvas');
    
    if (!video || !canvas) return;
    
    const context = canvas.getContext('2d');
    
    // Ajuster la taille du canvas à la vidéo
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    
    // Fonction de scan QR répétée
    function scanQR() {
        if (video.readyState === video.HAVE_ENOUGH_DATA) {
            // Dessiner l'image vidéo sur le canvas
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            // Obtenir les données d'image
            const imageData = context.getImageData(0, 0, canvas.width, canvas.height);
            
            // Vérifier si jsQR est disponible
            if (typeof jsQR !== 'undefined') {
                const code = jsQR(imageData.data, imageData.width, imageData.height);
                
                if (code) {
                    console.log('QR Code détecté:', code.data);
                    
                    // Remplir automatiquement le champ de saisie
                    document.getElementById('ticketCode').value = code.data;
                    
                    // Déclencher automatiquement la vérification
                    processTicket();
                    
                    // Arrêter la caméra après détection
                    toggleCamera();
                    
                    // Effet visuel de succès
                    playSuccessSound();
                }
            } else {
                console.warn('Bibliothèque jsQR non chargée. Seule la saisie manuelle est disponible.');
            }
        }
    }
    
    // Scanner toutes les 100ms
    window.qrDetectionInterval = setInterval(scanQR, 100);
}

// Test des permissions caméra
async function testCameraPermissions() {
    try {
        let permission = 'unavailable';
        
        if (navigator.permissions) {
            const permissionStatus = await navigator.permissions.query({ name: 'camera' });
            permission = permissionStatus.state;
        }
        
        let message = '';
        let alertClass = '';
        
        switch (permission) {
            case 'granted':
                message = 'Permissions caméra accordées ✅';
                alertClass = 'alert-success';
                break;
            case 'denied':
                message = 'Permissions caméra refusées ❌ - Consultez l\'aide';
                alertClass = 'alert-danger';
                break;
            case 'prompt':
                message = 'Permissions caméra non définies - Elles seront demandées lors de l\'activation';
                alertClass = 'alert-info';
                break;
            default:
                message = 'Impossible de vérifier les permissions caméra';
                alertClass = 'alert-warning';
        }
        
        // Afficher le résultat
        const testResult = document.createElement('div');
        testResult.className = `alert ${alertClass} alert-dismissible fade show mt-2`;
        testResult.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.querySelector('.scanner-page').insertBefore(testResult, document.querySelector('.row'));
        
        // Auto-supprimer après 5 secondes
        setTimeout(() => {
            testResult.remove();
        }, 5000);
        
    } catch (error) {
        console.error('Erreur test permissions:', error);
        alert('Erreur lors du test des permissions');
    }
}

// ========== RESTE DU CODE ORIGINAL ==========

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