@props(['cart'])
{{-- Lignes de commande (lecture seule) du checkout. $cart : lignes de session recalculées par le contrôleur. --}}
<ul class="order-lines">
    @foreach($cart as $item)
        <li class="order-line">
            @if(!empty($item['event_image']) && \Illuminate\Support\Facades\Storage::exists($item['event_image']))
                <img src="{{ Storage::url($item['event_image']) }}" alt="" class="order-line__img" width="56" height="56" loading="lazy" decoding="async" onerror="this.replaceWith(Object.assign(document.createElement('span'),{className:'order-line__img'}))">
            @else
                <span class="order-line__img" aria-hidden="true"><i class="fas fa-calendar-alt"></i></span>
            @endif
            <div>
                <p class="order-line__title">{{ $item['event_title'] }}</p>
                <p class="order-line__meta"><i class="fas fa-calendar me-1" aria-hidden="true"></i>{{ $item['event_date'] ?? 'Date à confirmer' }}@if(!empty($item['event_venue'])) &middot; {{ $item['event_venue'] }}@endif</p>
                <p class="order-line__meta">{{ $item['ticket_name'] }} &times; {{ $item['quantity'] }}</p>
            </div>
            <div class="order-line__price">{{ \App\Helpers\CurrencyHelper::formatPrice($item['total_price']) }}</div>
        </li>
    @endforeach
</ul>
