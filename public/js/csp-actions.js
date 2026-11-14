/*
 * csp-actions.js : remplace les attributs d'événement inline (onclick, onchange, onsubmit, onerror...)
 * par de la délégation d'événements, compatible avec une CSP stricte (script-src-attr 'none').
 *
 * Aucun eval, aucun new Function, aucune interprétation de chaîne de code : seuls les noms enregistrés
 * dans le registre CspActions sont appelables. Les arguments passent par des attributs data-*.
 *
 *   data-confirm="Message"          bouton, lien ou formulaire : confirm() au clic / à la soumission
 *   data-on-click="nom"             appelle CspActions.run(nom) : handler(element, event)
 *   data-on-change="nom"            idem au changement
 *   data-on-submit="nom"            idem à la soumission
 *   data-on-input="nom"             idem à la saisie
 *   data-img-fallback="hide|placeholder"   image en erreur : masquée ou remplacée par un <span> de même classe
 *
 * Enregistrer un gestionnaire (les scripts de page s'exécutent avant ce fichier, d'où DOMContentLoaded) :
 *   document.addEventListener('DOMContentLoaded', function () { CspActions.register('mon-action', fn); });
 */
(function () {
    'use strict';

    if (window.CspActions && window.CspActions.__ready) { return; }

    var registry = Object.create(null);

    function register(name, fn) {
        if (name && typeof name === 'object') {
            Object.keys(name).forEach(function (key) { register(key, name[key]); });
            return;
        }
        if (typeof name !== 'string' || typeof fn !== 'function') {
            throw new TypeError('CspActions.register(nom, fonction)');
        }
        registry[name] = fn;
    }

    function run(name, element, event) {
        var fn = registry[name];
        if (typeof fn !== 'function') {
            if (window.console) { console.warn('CspActions : action inconnue "' + name + '"'); }
            return undefined;
        }
        return fn.call(element, element, event);
    }

    // Actions intégrées (motifs répétés).
    register({
        // Retour à la page précédente.
        'history-back': function () { window.history.back(); },
        // Impression de la page.
        'print': function () { window.print(); },
        // Rechargement de la page.
        'reload': function () { window.location.reload(); },
        // Empêche l'action par défaut (lien href="#" par exemple).
        'prevent': function (el, e) { if (e) { e.preventDefault(); } },
        // Soumet le formulaire parent (au changement d'un select, par exemple) ou celui désigné par data-form="id".
        'submit-form': function (el, e) {
            var form = el.dataset.form ? document.getElementById(el.dataset.form) : (el.form || el.closest('form'));
            if (e && el.dataset.form) { e.preventDefault(); }
            if (form) { form.submit(); }
        },
        // Idem, en annulant d'abord l'action par défaut du lien (déconnexion par lien).
        'submit-form-prevent': function (el, e) {
            if (e) { e.preventDefault(); }
            var form = el.dataset.form ? document.getElementById(el.dataset.form) : (el.form || el.closest('form'));
            if (form) { form.submit(); }
        }
    });

    // Confirmation : phase de capture, pour qu'un refus coupe aussi les autres écouteurs (anti double-soumission...).
    function askConfirm(el, event) {
        if (window.confirm(el.getAttribute('data-confirm'))) { return; }
        event.preventDefault();
        event.stopPropagation();
        event.stopImmediatePropagation();
    }

    document.addEventListener('click', function (event) {
        var el = event.target && event.target.closest ? event.target.closest('[data-confirm]') : null;
        if (el && el.tagName !== 'FORM') { askConfirm(el, event); }
    }, true);

    document.addEventListener('submit', function (event) {
        var el = event.target;
        if (el && el.getAttribute && el.hasAttribute('data-confirm')) { askConfirm(el, event); }
    }, true);

    // Gestionnaires data-on-* : on remonte de la cible vers la racine, comme le ferait la propagation native.
    function delegate(type) {
        var attr = 'data-on-' + type;
        document.addEventListener(type, function (event) {
            var el = event.target && event.target.closest ? event.target : null;
            while (el && el !== document) {
                if (el.hasAttribute && el.hasAttribute(attr)) {
                    run(el.getAttribute(attr), el, event);
                    if (event.cancelBubble) { break; }
                }
                el = el.parentElement;
            }
        });
    }
    ['click', 'change', 'submit', 'input'].forEach(delegate);

    // Repli d'image. L'événement « error » ne remonte pas : écoute en phase de capture.
    function applyFallback(img) {
        var mode = img.getAttribute('data-img-fallback');
        if (mode === 'placeholder') {
            var span = document.createElement('span');
            span.className = img.className;
            img.replaceWith(span);
        } else if (mode === 'hide') {
            img.style.display = 'none';
        }
    }
    document.addEventListener('error', function (event) {
        var img = event.target;
        if (img && img.tagName === 'IMG' && img.hasAttribute('data-img-fallback')) { applyFallback(img); }
    }, true);

    // Images déjà en échec avant l'exécution de ce script (chargé en defer).
    document.addEventListener('DOMContentLoaded', function () {
        var imgs = document.querySelectorAll('img[data-img-fallback]');
        for (var i = 0; i < imgs.length; i++) {
            if (imgs[i].complete && imgs[i].naturalWidth === 0 && imgs[i].getAttribute('src')) { applyFallback(imgs[i]); }
        }
    });

    window.CspActions = { register: register, run: run, __ready: true };
})();
