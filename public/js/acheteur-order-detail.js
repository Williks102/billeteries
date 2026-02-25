function showQRCode(ticketCode, qrCodeUrl) {
    document.getElementById('ticket-code-display').textContent = ticketCode;
    
    // Afficher le QR code
    const qrContent = document.getElementById('qr-content');
    if (qrCodeUrl.startsWith('data:image')) {
        // QR code en base64
        qrContent.innerHTML = `<img src="${qrCodeUrl}" alt="QR Code" class="img-fluid" style="max-width: 300px;">`;
    } else {
        // URL de vérification
        qrContent.innerHTML = `
            <div class="qr-placeholder">
                <p class="text-muted">QR Code disponible après génération</p>
                <a href="${qrCodeUrl}" target="_blank" class="btn btn-sm btn-primary">
                    <i class="fas fa-external-link-alt me-1"></i>Vérifier le billet
                </a>
            </div>
        `;
    }
    
    // Afficher le modal
    new bootstrap.Modal(document.getElementById('qrModal')).show();
}
