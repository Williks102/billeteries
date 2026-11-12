// Barre d'achat mobile : ouvre le sélecteur de billets déjà présent sur la page.
document.addEventListener('click', function (e) {
    if (e.target.closest('[data-open-ticket-modal]') && typeof window.openTicketModal === 'function') {
        window.openTicketModal();
    }
});
