@props(['event'])
{{-- Barre d'achat fixe en bas (mobile seulement, styles : public/css/event-detail.css).
     Le bouton ouvre le même sélecteur de billets que « Choisir mes billets » (js/event-buy-bar.js). --}}
<div class="buy-bar" role="region" aria-label="Achat de billets">
    <p class="buy-bar__price">{{ $event->startingPriceLabel() }}</p>
    <button type="button" class="btn btn-primary buy-bar__btn" data-open-ticket-modal>
        Choisir mes billets
    </button>
</div>
