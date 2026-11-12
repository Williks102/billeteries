// Partage natif : le bouton reste caché si le navigateur ne le supporte pas (les liens WhatsApp / Facebook / X servent de repli).
(function () {
    if (!navigator.share) { return; }
    document.querySelectorAll('[data-native-share]').forEach(function (btn) {
        btn.hidden = false;
        btn.addEventListener('click', function () {
            navigator.share({ title: btn.dataset.shareTitle, url: btn.dataset.shareUrl }).catch(function () { /* annulé par l'utilisateur */ });
        });
    });
})();
