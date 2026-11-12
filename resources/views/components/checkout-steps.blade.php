@props(['current' => 1, 'labels' => ['Billets', 'Informations', 'Paiement']])
{{-- Barre d'étapes unique du parcours d'achat. Numéro + libellé ; l'étape courante porte aria-current="step". --}}
<ol class="parcours-steps" aria-label="Étapes de la commande">
    @foreach($labels as $i => $label)
        @php($n = $i + 1)
        <li class="parcours-steps__item {{ $n < $current ? 'is-done' : '' }} {{ $n === (int) $current ? 'is-current' : '' }}" @if($n === (int) $current) aria-current="step" @endif>
            <span class="parcours-steps__num" aria-hidden="true">@if($n < $current)<i class="fas fa-check"></i>@else{{ $n }}@endif</span>
            <span class="parcours-steps__label">{{ $label }}@if($n < $current) <span class="visually-hidden">(terminée)</span>@endif</span>
        </li>
    @endforeach
</ol>
