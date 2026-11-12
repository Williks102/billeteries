@props(['items' => [], 'label' => 'Navigation principale'])
{{-- Barre de navigation en bas (mobile seulement, styles : charte.css « Barre de navigation en bas »).
     $items : [['label' => 'Billets', 'icon' => 'fa-ticket-alt', 'route' => 'acheteur.tickets.index', 'active' => 'acheteur.tickets.*'], ...] --}}
<nav class="bottom-nav" aria-label="{{ $label }}">
    <ul class="bottom-nav__list">
        @foreach($items as $item)
            @php $isActive = request()->routeIs($item['active'] ?? $item['route']); @endphp
            <li class="bottom-nav__item">
                <a href="{{ route($item['route']) }}"
                   class="bottom-nav__link{{ $isActive ? ' is-active' : '' }}"
                   @if($isActive) aria-current="page" @endif>
                    <i class="fas {{ $item['icon'] }}" aria-hidden="true"></i>
                    <span class="bottom-nav__label">{{ $item['label'] }}</span>
                </a>
            </li>
        @endforeach
    </ul>
</nav>
