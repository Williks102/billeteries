{{-- =============================================== --}}
{{-- Scanner de Billets - Version Corrigée --}}
{{-- =============================================== --}}
@extends('layouts.promoteur')

@section('title', 'Scanner de Billets - ClicBillet CI')
@section('breadcrumb', 'Scanner')

@section('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

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
            <button class="btn btn-outline-primary" data-on-click="scanner-stats">
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
                        <i class="fas fa-scan me-2"></i>Scanner / Saisie
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Saisie manuelle -->
                    <div class="mb-4">
                        <label for="ticketCode" class="form-label fw-bold">Code du billet</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-ticket-alt"></i>
                            </span>
                            <input type="text" 
                                   class="form-control form-control-lg" 
                                   id="ticketCode" 
                                   placeholder="TKT-XXXXXXXX"
                                   style="text-transform: uppercase;"
                                   maxlength="12">
                            <button class="btn btn-success btn-lg" type="button" data-on-click="scanner-process-ticket">
                                <i class="fas fa-search me-2"></i>Vérifier
                            </button>
                        </div>
                        <div class="form-text">Format: TKT suivi de 8 caractères alphanumériques</div>
                    </div>

                    <!-- Boutons caméra -->
                    <div class="text-center mb-3">
                        <button type="button" class="btn btn-outline-info btn-lg me-2" data-on-click="scanner-camera-toggle">
                            <i class="fas fa-camera me-2"></i>Activer la caméra QR
                        </button>
                        <button type="button" class="btn btn-outline-secondary" data-on-click="scanner-camera-test">
                            <i class="fas fa-cog me-2"></i>Test permissions
                        </button>
                    </div>

                    <!-- Container caméra -->
                    <div id="cameraContainer" style="display: none;" class="text-center mb-4">
                        <!-- Le contenu sera ajouté dynamiquement -->
                    </div>

                    <!-- Résultats du scan -->
                    <div id="scanResult" style="display: none;"></div>
                </div>
            </div>
        </div>

        <!-- Panneau latéral -->
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h6 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>Aide rapide
                    </h6>
                </div>
                <div class="card-body">
                    <h6 class="fw-bold">Résolution de problèmes :</h6>
                    <ul class="small">
                        <li><strong>Scanner retourne "undefined" :</strong> Vérifiez que jsQR est chargé</li>
                        <li><strong>Caméra ne s'active pas :</strong> Autorisez l'accès dans votre navigateur</li>
                        <li><strong>QR code non détecté :</strong> Assurez-vous que le code est bien visible</li>
                        <li><strong>Erreur de connexion :</strong> Vérifiez votre connexion internet</li>
                    </ul>
                    
                    <div class="mt-3">
                        <button class="btn btn-sm btn-outline-primary" data-on-click="reload">
                            <i class="fas fa-refresh me-1"></i>Recharger la page
                        </button>
                    </div>
                </div>
            </div>

            <!-- Statistiques rapides -->
            <div class="card shadow-sm mt-3">
                <div class="card-header bg-light">
                    <h6 class="mb-0">
                        <i class="fas fa-chart-pie me-2"></i>Statistiques
                    </h6>
                </div>
                <div class="card-body" id="quickStats">
                    <div class="text-center">
                        <div class="spinner-border spinner-border-sm text-primary" role="status">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                        <p class="mt-2 small text-muted">Chargement des statistiques...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- Bibliothèque jsQR (CORRECTION PRINCIPALE) -->
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js"></script>

<script @cspNonce>

// Actions data-on-* (voir public/js/csp-actions.js)
document.addEventListener('DOMContentLoaded', function () {
    CspActions.register({
        'scanner-stats': function () { showStats(); },
        'scanner-process-ticket': function () { processTicket(); },
        'scanner-camera-toggle': function (el, e) { toggleCamera(e); },
        'scanner-camera-test': function () { testCameraPermissions(); },
        'scanner-camera-stop': function () { stopCamera(); },
        'scanner-camera-retry': function () { retryCamera(); },
        'scanner-camera-help': function () { showCameraHelp(); },
        'scanner-camera-help-retry': function () { retryCamera(); bootstrap.Modal.getInstance(document.getElementById('cameraHelpModal')).hide(); }
    });
});

document.addEventListener('DOMContentLoaded', function() {
    // Vérifier que jsQR est bien chargé
    if (typeof jsQR === 'undefined') {
        console.error('jsQR non chargé - Scanner QR indisponible');
        showError('Bibliothèque de scan QR non chargée. Rechargez la page.');
    } else {
        console.log('jsQR chargé avec succès');
    }
    
    // Charger les statistiques
    loadQuickStats();
});

// ========== FONCTION PRINCIPALE DE TRAITEMENT (CORRIGÉE) ==========
async function processTicket() {
    const ticketCode = document.getElementById('ticketCode').value.trim().toUpperCase();
    const submitButton = document.querySelector('button[data-on-click="scanner-process-ticket"]');
    
    if (!ticketCode) {
        showError('Veuillez saisir ou scanner un code de billet');
        return;
    }
    
    // Validation du format
    if (!/^TKT-[A-Z0-9]{8}$/.test(ticketCode)) {
        showError('Format de code invalide. Format attendu: TKT-XXXXXXXX');
        return;
    }
    
    // Désactiver le bouton pendant le traitement
    if (submitButton) {
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Vérification...';
    }
    
    try {
        const response = await fetch('{{ route("promoteur.scanner.verify") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                ticket_code: ticketCode
            })
        });
        
        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || `Erreur HTTP ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.success) {
            showSuccess(data);
            document.getElementById('ticketCode').value = ''; // Vider le champ
            updateScanCount();
        } else {
            showError(data.message || 'Erreur lors de la vérification', data.ticket_info);
        }
        
    } catch (error) {
        console.error('Erreur processTicket:', error);
        showError('Erreur de connexion: ' + error.message);
    } finally {
        // Réactiver le bouton
        if (submitButton) {
            submitButton.disabled = false;
            submitButton.innerHTML = '<i class="fas fa-search me-2"></i>Vérifier';
        }
    }
}

// ========== GESTION CAMÉRA (CORRIGÉE) ==========
async function startCamera() {
    // Vérifier le support de getUserMedia
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        showCameraError('Votre navigateur ne supporte pas l\'accès à la caméra. Utilisez Chrome, Firefox ou Safari récent.');
        return;
    }

    // Vérifier si on est en HTTPS (requis pour la caméra)
    if (location.protocol !== 'https:' && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
        showCameraError('L\'accès à la caméra nécessite une connexion HTTPS sécurisée.');
        return;
    }

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

    try {
        const stream = await navigator.mediaDevices.getUserMedia(constraints);
        
        // Succès - afficher le flux vidéo
        cameraContainer.innerHTML = `
            <video id="cameraFeed" width="100%" height="300" style="border-radius: 10px;" autoplay playsinline></video>
            <canvas id="cameraCanvas" style="display: none;"></canvas>
            <div class="mt-2 text-center">
                <button type="button" class="btn btn-danger btn-sm" data-on-click="scanner-camera-stop">
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
        
    } catch (err) {
        console.error('Erreur caméra:', err);
        
        let errorMessage = 'Impossible d\'accéder à la caméra.';
        
        switch (err.name) {
            case 'NotAllowedError':
            case 'PermissionDeniedError':
                errorMessage = 'Accès à la caméra refusé. Veuillez autoriser l\'accès dans votre navigateur.';
                break;
            case 'NotFoundError':
            case 'DevicesNotFoundError':
                errorMessage = 'Aucune caméra trouvée sur cet appareil.';
                break;
            case 'NotReadableError':
            case 'TrackStartError':
                errorMessage = 'La caméra est déjà utilisée par une autre application.';
                break;
            case 'OverconstrainedError':
                errorMessage = 'Les paramètres de caméra demandés ne sont pas supportés.';
                break;
            case 'NotSupportedError':
                errorMessage = 'Fonctionnalité caméra non supportée par votre navigateur.';
                break;
        }
        
        showCameraError(errorMessage, err);
    }
}

// ========== DÉTECTION QR AMÉLIORÉE ==========
// Variable globale pour empêcher le rescan immédiat
let lastScannedCode = null;
let lastScanTime = 0;
const SCAN_COOLDOWN = 5000; // 5 secondes de cooldown

function startQRDetection() {
    const video = document.getElementById('cameraFeed');
    const canvas = document.getElementById('cameraCanvas');
    
    if (!video || !canvas) {
        console.error('Éléments video ou canvas introuvables');
        return;
    }
    
    const context = canvas.getContext('2d');
    
    function scanQR() {
        if (video.readyState === video.HAVE_ENOUGH_DATA) {
            // Ajuster la taille du canvas à la vidéo
            canvas.width = video.videoWidth || 640;
            canvas.height = video.videoHeight || 480;
            
            // Dessiner l'image vidéo sur le canvas
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            // Obtenir les données d'image
            const imageData = context.getImageData(0, 0, canvas.width, canvas.height);
            
            // Vérifier si jsQR est disponible
            if (typeof jsQR !== 'undefined') {
                const code = jsQR(imageData.data, imageData.width, imageData.height, {
                    inversionAttempts: "dontInvert"
                });
                
                if (code && code.data) {
                    console.log('QR Code détecté:', code.data);
                    
                    // Extraire le code du ticket de l'URL si nécessaire
                    let ticketCode = code.data;
                    if (code.data.includes('/verify-ticket/')) {
                        const matches = code.data.match(/\/verify-ticket\/([A-Z0-9-]+)/);
                        if (matches && matches[1]) {
                            ticketCode = matches[1];
                        }
                    }
                    
                    // ✅ PROTECTION CONTRE LE RESCAN IMMÉDIAT
                    const now = Date.now();
                    if (lastScannedCode === ticketCode && (now - lastScanTime) < SCAN_COOLDOWN) {
                        console.log('⏸️ Scan ignoré - même code scanné il y a moins de 5 secondes');
                        return; // Ignorer ce scan
                    }
                    
                    // ✅ Mémoriser le dernier scan
                    lastScannedCode = ticketCode;
                    lastScanTime = now;
                    
                    // Remplir automatiquement le champ de saisie
                    document.getElementById('ticketCode').value = ticketCode;
                    
                    // ✅ ARRÊTER LA CAMÉRA AVANT DE TRAITER
                    stopCamera();
                    
                    // Déclencher la vérification
                    processTicket();
                    
                    // Effet visuel de succès
                    if (typeof playSuccessSound === 'function') {
                        playSuccessSound();
                    }
                    
                    return; // Arrêter la détection
                }
            } else {
                console.warn('Bibliothèque jsQR non chargée - Scanner QR indisponible');
            }
        }
    }
    
    // Scanner toutes les 150ms (optimisé)
    window.qrDetectionInterval = setInterval(scanQR, 150);
}

// ========== GESTION BOUTONS CAMÉRA ==========
function toggleCamera(evt) {
    const container = document.getElementById('cameraContainer');
    const button = (evt || window.event).target.closest('button');
    
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

// ========== AFFICHAGE DES RÉSULTATS ==========
function showSuccess(data) {
    const scanResult = document.getElementById('scanResult');
    const ticket = data.ticket_info || data.ticket;
    
    scanResult.innerHTML = `
        <div class="alert alert-success alert-dismissible fade show">
            <div class="d-flex align-items-center">
                <i class="fas fa-check-circle fa-2x me-3"></i>
                <div class="flex-grow-1">
                    <h5 class="alert-heading">✅ Billet Valide Scanné !</h5>
                    <p class="mb-1"><strong>${ticket.event || 'Événement'}</strong></p>
                    <p class="mb-1">Code: ${ticket.code} • Type: ${ticket.type || 'N/A'}</p>
                    <p class="mb-1">Client: ${ticket.client || 'N/A'}</p>
                    <small>Scanné le ${new Date().toLocaleString('fr-FR')}</small>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    scanResult.style.display = 'block';
    
    // Son de succès (optionnel)
    playSuccessSound();
}

function showError(message, ticket = null) {
    const scanResult = document.getElementById('scanResult');
    
    let extraInfo = '';
    if (ticket) {
        if (ticket.status === 'used') {
            extraInfo = `<p class="mb-1">⚠️ Billet déjà utilisé</p>`;
            if (ticket.used_at) {
                extraInfo += `<small>Utilisé le ${ticket.used_at}</small>`;
            }
        } else {
            extraInfo = `<p class="mb-1">Statut: ${ticket.status}</p>`;
        }
    }
    
    scanResult.innerHTML = `
        <div class="alert alert-danger alert-dismissible fade show">
            <div class="d-flex align-items-center">
                <i class="fas fa-times-circle fa-2x me-3"></i>
                <div class="flex-grow-1">
                    <h5 class="alert-heading">❌ Erreur de scan</h5>
                    <p class="mb-1">${message}</p>
                    ${extraInfo}
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    scanResult.style.display = 'block';
    
    // Son d'erreur (optionnel)
    playErrorSound();
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
                        <button type="button" class="btn btn-primary btn-sm me-2" data-on-click="scanner-camera-retry">
                            <i class="fas fa-redo me-1"></i>Réessayer
                        </button>
                        <button type="button" class="btn btn-secondary btn-sm" data-on-click="scanner-camera-help">
                            <i class="fas fa-question-circle me-1"></i>Aide
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
}

// ========== FONCTIONS UTILITAIRES ==========
function retryCamera() {
    const container = document.getElementById('cameraContainer');
    container.innerHTML = '';
    startCamera();
}

function showCameraHelp() {
    const helpModal = `
        <div class="modal fade" id="cameraHelpModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Aide - Problèmes de caméra</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <h6>Solutions courantes :</h6>
                        <ul>
                            <li><strong>Permissions refusées :</strong> Cliquez sur l'icône caméra dans la barre d'adresse</li>
                            <li><strong>Caméra occupée :</strong> Fermez les autres onglets utilisant la caméra</li>
                            <li><strong>Navigation non sécurisée :</strong> Assurez-vous d'être en HTTPS</li>
                            <li><strong>Navigateur incompatible :</strong> Utilisez Chrome, Firefox ou Safari récent</li>
                        </ul>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                        <button type="button" class="btn btn-primary" data-on-click="scanner-camera-help-retry">
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

// ========== TEST PERMISSIONS ==========
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
                message = '✅ Permissions caméra accordées';
                alertClass = 'alert-success';
                break;
            case 'denied':
                message = '❌ Permissions caméra refusées - Consultez l\'aide';
                alertClass = 'alert-danger';
                break;
            case 'prompt':
                message = '🔄 Permissions caméra non définies - Elles seront demandées lors de l\'activation';
                alertClass = 'alert-info';
                break;
            default:
                message = '⚠️ Impossible de vérifier les permissions caméra';
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

// ========== STATISTIQUES ==========
function loadQuickStats() {
    const statsContainer = document.getElementById('quickStats');
    
    // Simulation de chargement des stats (remplacez par votre API)
    setTimeout(() => {
        statsContainer.innerHTML = `
            <div class="row text-center">
                <div class="col-6">
                    <div class="border-end">
                        <h5 class="text-primary mb-0">24</h5>
                        <small class="text-muted">Aujourd'hui</small>
                    </div>
                </div>
                <div class="col-6">
                    <h5 class="text-success mb-0">156</h5>
                    <small class="text-muted">Cette semaine</small>
                </div>
            </div>
        `;
    }, 1000);
}

function updateScanCount() {
    const scanCountElement = document.getElementById('scan-count');
    if (scanCountElement) {
        const currentCount = parseInt(scanCountElement.textContent.match(/\d+/)[0]) || 0;
        scanCountElement.textContent = `${currentCount + 1} scannés aujourd'hui`;
    }
}

function showStats() {
    // Implementer l'affichage des statistiques détaillées
    alert('Fonctionnalité statistiques en cours de développement');
}

// ========== EFFETS SONORES (OPTIONNEL) ==========
function playSuccessSound() {
    try {
        // Son de succès court
        const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+XwtmMcBjiR2O/NeSsFJHfH8N2QQAoUXrTp66hVFApGn+XwtmMcBjiR2O/NeSsFJHfH8N2QQAoUXrTp66hVFApGn+XwtmMcBjiR2O/NeSsFJHfH8N2QQAoUXrTp66hVFApGn+XwtmMcBjiR2O/NeSsFJHfH8N2QQAoUXrTp66hVFApGn+XwtmMcBjiR2O/NeSsFJHfH8N2QQAoUXrTp66hVFApGn+XwtmMcBjiR2O/NeSsFJHfH8N2QQAoUXrTp66hVFApGn+XwtmMcBjiR2O/NeSsFJHfH8N2QQAoUXrTp66hVFApGn+XwtmMcBjiR2O/NeSsFJHfH8N2QQAoUXrTp66hVFApGn+XwtmMcBjiR2O/NeSsFJHfH8N2QQAoUXrTp66hVFApGn+XwtmMcBjiR2O/NeSsFJHfH8N2QQAoUXrTp66hVFApGn+XwtmMcBjiR2O/NeSsFJHfH8N2QQAoUXrTp66hVFApGn+XwtmMcBjiR2O/NeSsFJHfH8N2QQAoUXrTp66hVFApGn+XwtmMcBjiR2O/NeSsFJHfH8N2QQAoUXrTp66hVFApGn+XwtmMcBjiR2O/NeSsFJHfH8N2QQAoUXrTp66hVFApGn+XwtmMcBjiR2O/NeSsFJHfH8N2QQAoUXrTp66hVFApGn+XwtmMcBjiR2O/NeSsFJHfH8N2QQAoUXrTp66hVFApGn+XwtmMcBjiR2O/NeSsFJHfH8N2QQAoUXrTp66hVFApGn+XwtmMcBjiR2O/NeSsFJHfH8N2QQAoUXrTp66hVFApGn+XwtmMcBjiR2O/NeSsFJHfH8N2QQAoUXrTp66hVFApGn+Xwt');
        audio.volume = 0.3;
        audio.play().catch(e => console.log('Son indisponible'));
    } catch (e) {
        // Son non critique
    }
}

function playErrorSound() {
    try {
        // Son d'erreur court
        const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+XwtmMcBjiR2O/NeSsFJHfH8N2QQAoUXrTp66hVFApGn+XwtmMcBjiR2O/NeSsFJHfH8N2QQAoUXrTp66hVFApGn+XwtmMcBjiR2O/NeSsFJHfH8N2QQAoUXrTp66hVFApGn+XwtmMcBjiR2O/NeSsFJHfH8N2QQAoUXrTp66hVFApGn+XwtmMcBjiR2O/NeSsFJHfH8N2QQAoUXrTp66hVFApGn+XwtmMcBjiR2O/NeSsFJHfH8N2QQAoUXrTp66hVFApGn+XwtmMcBjiR2O/NeSsFJHfH8N2QQAoUXrTp66hVFApGn+XwtmMcBjiR2O/NeSsFJHfH8N2QQAoUXrTp66hVFApGn+XwtmMcBjiR2O/NeSsFJHfH8N2QQAoUXrTp66hVFApGn+XwtmMcBjiR2O/NeSsFJHfH8N2QQAoUXrTp66hVFApGn+XwtmMcBjiR2O/NeSsFJHfH8N2QQAoUXrTp66hVFApGn+XwtmMcBjiR2O/NeSsFJHfH8N2QQAoUXrTp66hVFApGn+XwtmMcBjiR2O/NeSsFJHfH8N2QQAoUXrTp66hVFApGn+XwtmMcBjiR2O/NeSsFJHfH8N2QQAoUXrTp66hVFApGn+XwtmMcBjiR2O/NeSsFJHfH8N2QQAoUXrTp66hVFApGn+XwtmMcBjiR2O/NeSsFJHfH8N2QQAoUXrTp66hVFApGn+XwtmMcBjiR2O/NeSsFJHfH8N2QQAoUXrTp66hVFApGn+XwtmMcBjiR2O/NeSsFJHfH8N2QQAoUXrTp66hVFApGn+XwtmMcBjiR2O/NeSsFJHfH8N2QQAoUXrTp66hVFApGn+XwtmMcBjiR2O/NeSsFJHfH8N2QQAoUXrTp66hVFApGn+XwtmMcBjiR2O/NeSsFJHfH8N2QQAoUXrTp66hVFApGn+XwtmMcBjiR2O/NeSsFJHfH8N2QQAoUXrTp66hVFApGn+Xwt');
        audio.volume = 0.3;
        audio.play().catch(e => console.log('Son indisponible'));
    } catch (e) {
        // Son non critique
    }
}

// ========== INITIALISATION ==========
// Permettre la saisie au clavier Enter
document.addEventListener('DOMContentLoaded', function() {
    const ticketInput = document.getElementById('ticketCode');
    if (ticketInput) {
        ticketInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                processTicket();
            }
        });
        
        // Formatage automatique en majuscules
        ticketInput.addEventListener('input', function(e) {
            e.target.value = e.target.value.toUpperCase();
        });
    }
});

// Nettoyage à la fermeture de la page
window.addEventListener('beforeunload', function() {
    if (window.qrDetectionInterval) {
        clearInterval(window.qrDetectionInterval);
    }
    stopCamera();
});
</script>
@endpush