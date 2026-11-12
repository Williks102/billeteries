{{-- Panier : une seule mise en page responsive (plus de doublon bureau / feuille mobile). Aucun minuteur :
     le stock n'est réservé qu'à la validation de la commande (voir le délai annoncé dans le checkout). --}}
@extends('layouts.app')

@section('title', 'Mon panier')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/parcours-achat.css') }}">
@endpush

@section('content')
<div class="container parcours-page">
    <h1>Mon panier</h1>
    <x-checkout-steps :current="1" />

    @if(empty($cart))
        <div class="parcours-card empty-state">
            <i class="fas fa-shopping-cart" aria-hidden="true"></i>
            <h2 class="h5">Votre panier est vide</h2>
            <p class="text-muted">Choisissez un événement pour y ajouter vos billets.</p>
            <a href="{{ route('events.all') }}" class="btn btn-primary btn-lg"><i class="fas fa-calendar-alt me-2" aria-hidden="true"></i>Voir les événements</a>
        </div>
    @else
        <div class="parcours-layout"
             id="cart-root"
             data-update-url="{{ route('cart.update') }}"
             data-remove-url="{{ route('cart.remove') }}"
             data-clear-url="{{ route('cart.clear') }}">
            <section class="parcours-card" aria-labelledby="cart-title">
                <h2 class="parcours-card__title" id="cart-title"><i class="fas fa-list-ul" aria-hidden="true"></i>Articles (<span data-cart-count>{{ $cartCount }}</span>)</h2>

                <div id="cart-status" class="cart-status alert" role="status" aria-live="polite"></div>

                <div id="cart-lines">
                    @foreach($cart as $key => $item)
                        <article class="cart-line" data-cart-key="{{ $key }}" data-unit-price="{{ (int) $item['unit_price'] }}" data-name="{{ $item['ticket_name'] }}">
                            @if(!empty($item['event_image']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($item['event_image']))
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($item['event_image']) }}" alt="" class="order-line__img" width="56" height="56" loading="lazy" decoding="async" onerror="this.replaceWith(Object.assign(document.createElement('span'),{className:'order-line__img'}))">
                            @else
                                <span class="order-line__img" aria-hidden="true"><i class="fas fa-calendar-alt"></i></span>
                            @endif
                            <div>
                                <h3 class="order-line__title h6">{{ $item['event_title'] }}</h3>
                                <p class="order-line__meta"><strong>{{ $item['ticket_name'] }}</strong> &middot; {{ \App\Helpers\CurrencyHelper::formatPrice($item['unit_price']) }} l'unité</p>
                                <p class="order-line__meta"><i class="fas fa-calendar me-1" aria-hidden="true"></i>{{ $item['event_date'] ?? 'Date à confirmer' }}@if(!empty($item['event_venue'])) &middot; {{ $item['event_venue'] }}@endif</p>
                            </div>

                            <div class="cart-line__actions">
                                <div class="qty-stepper" role="group" aria-label="Quantité de {{ $item['ticket_name'] }}">
                                    <button type="button" class="qty-btn" data-qty-step="-1" aria-label="Diminuer la quantité de {{ $item['ticket_name'] }}" {{ $item['quantity'] <= 1 ? 'disabled' : '' }}>
                                        <i class="fas fa-minus" aria-hidden="true"></i>
                                    </button>
                                    <input type="text" class="form-control qty-input" inputmode="numeric" pattern="[0-9]*" autocomplete="off"
                                           value="{{ $item['quantity'] }}" data-qty-input aria-label="Quantité de {{ $item['ticket_name'] }}">
                                    <button type="button" class="qty-btn" data-qty-step="1" aria-label="Augmenter la quantité de {{ $item['ticket_name'] }}">
                                        <i class="fas fa-plus" aria-hidden="true"></i>
                                    </button>
                                </div>
                                <span class="cart-line__total" data-line-total>{{ \App\Helpers\CurrencyHelper::formatPrice($item['total_price']) }}</span>
                                <button type="button" class="cart-remove" data-remove aria-label="Retirer {{ $item['ticket_name'] }} du panier">
                                    <i class="fas fa-trash-alt" aria-hidden="true"></i>
                                </button>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="cluster mt-3">
                    <a href="{{ route('events.all') }}" class="btn btn-outline-primary"><i class="fas fa-plus me-2" aria-hidden="true"></i>Ajouter d'autres billets</a>
                    <button type="button" class="btn btn-outline-danger" id="cart-clear"><i class="fas fa-trash me-2" aria-hidden="true"></i>Vider le panier</button>
                </div>
                @guest
                    <p class="mt-3 mb-0">Vous avez déjà un compte ? <a href="{{ route('login') }}?redirect={{ urlencode(route('checkout.show')) }}" class="link-touch">Me connecter</a></p>
                @endguest
            </section>

            <aside class="parcours-aside">
                <div class="cart-checkout-bar parcours-card">
                    <div class="summary-row summary-row--total">
                        <span>Total</span>
                        <span class="summary-amount" id="cart-total">{{ \App\Helpers\CurrencyHelper::formatPrice($cartTotal) }}</span>
                    </div>
                    @guest
                        <a href="{{ route('checkout.guest.show') }}" class="btn btn-primary btn-lg btn-block-touch mt-3">
                            <i class="fas fa-credit-card" aria-hidden="true"></i>Passer la commande
                        </a>
                    @else
                        <a href="{{ route('checkout.show') }}" class="btn btn-primary btn-lg btn-block-touch mt-3">
                            <i class="fas fa-credit-card" aria-hidden="true"></i>Passer la commande
                        </a>
                    @endguest
                    <p class="cart-checkout-extra small text-muted text-center mt-2 mb-0">Vous choisirez votre mode de paiement à l'étape suivante.</p>
                </div>
            </aside>
        </div>
    @endif
</div>
@if(!empty($cart))
<script src="{{ asset('js/cart.js') }}" defer></script>
@endif
@endsection
