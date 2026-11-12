@props(['event', 'suffix' => 'main'])
{{-- Liste des billets de la fiche : nom, prix (ou « Gratuit »), état de vente. Aucune action d'achat ici (bouton / barre d'achat). --}}
@if($event->ticketTypes->isNotEmpty())
<section class="detail-section" aria-labelledby="tickets-title-{{ $suffix }}">
    <h2 class="detail-section__title" id="tickets-title-{{ $suffix }}"><i class="fas fa-ticket-alt" aria-hidden="true"></i>Billets</h2>
    <ul class="ticket-list">
        @foreach($event->ticketTypes as $ticketType)
        @php $status = $ticketType->saleStatus($event); @endphp
        <li class="ticket-list__item">
            <div class="ticket-list__main">
                <span class="ticket-list__name">{{ $ticketType->name }}</span>
                @if($ticketType->description)
                <span class="ticket-list__desc">{{ $ticketType->description }}</span>
                @endif
            </div>
            <div class="ticket-list__side">
                <span class="ticket-list__price">{{ \App\Helpers\CurrencyHelper::formatPrice($ticketType->price) }}</span>
                @if($status === 'available')
                    <x-ticket-availability-badge :remaining="$ticketType->publicRemaining()" />
                @else
                    <span class="badge-state badge-state--{{ $status === 'sold_out' ? 'soldout' : 'neutral' }}"><i class="fas {{ $status === 'sold_out' ? 'fa-times-circle' : 'fa-lock' }}" aria-hidden="true"></i>{{ $ticketType->saleStatusLabel($event) }}</span>
                @endif
            </div>
        </li>
        @endforeach
    </ul>
</section>
@endif
