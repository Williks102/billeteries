@props(['event', 'suffix' => 'main'])
{{-- Informations pratiques : date longue, horaire, lieu + itinéraire, organisateur, places. Partagé mobile / desktop. --}}
<section class="detail-section detail-section--practical" aria-labelledby="practical-title-{{ $suffix }}">
    <h2 class="detail-section__title" id="practical-title-{{ $suffix }}">Informations pratiques</h2>
    <ul class="info-list">
        <li class="info-row">
            <span class="info-row__icon"><i class="fas fa-calendar-alt" aria-hidden="true"></i></span>
            <div class="info-row__body">
                <span class="info-row__label">{{ $event->long_date_label }}</span>
                @if($event->time_range_label !== '')
                <span class="info-row__value">{{ $event->time_range_label }}</span>
                @endif
            </div>
        </li>
        <li class="info-row">
            <span class="info-row__icon"><i class="fas fa-map-marker-alt" aria-hidden="true"></i></span>
            <div class="info-row__body">
                <span class="info-row__label">{{ $event->venue }}</span>
                @if($event->address)
                <span class="info-row__value">{{ $event->address }}</span>
                @endif
                @if($event->directionsUrl())
                <a class="info-row__link" href="{{ $event->directionsUrl() }}" target="_blank" rel="noopener noreferrer">
                    <i class="fas fa-route" aria-hidden="true"></i>Itinéraire<span class="visually-hidden"> (s'ouvre dans un nouvel onglet)</span>
                </a>
                @endif
            </div>
        </li>
        <li class="info-row">
            <span class="info-row__icon"><i class="fas fa-user-tie" aria-hidden="true"></i></span>
            <div class="info-row__body">
                <span class="info-row__label">Organisé par {{ $event->promoteur->name ?? 'ClicBillet CI' }}</span>
            </div>
        </li>
        @if($event->ticketTypes->isNotEmpty())
        <li class="info-row">
            <span class="info-row__icon"><i class="fas fa-users" aria-hidden="true"></i></span>
            <div class="info-row__body">
                <span class="info-row__label">{{ $event->placesLabel() }}</span>
            </div>
        </li>
        @endif
    </ul>
</section>
