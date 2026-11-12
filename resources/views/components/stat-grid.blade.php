{{-- Grille de <x-stat-card> : 1 colonne (390 px), 2 dès 576 px, 3 dès 768 px, 4 dès 1200 px (cols="4" par défaut, cols="3" pour rester à 3). Sans défilement horizontal. --}}
@props(['cols' => 4, 'label' => null])
<div {{ $attributes->class(['stat-grid', 'stat-grid--'.((int) $cols === 3 ? '3' : '4')]) }} @if($label) role="group" aria-label="{{ $label }}" @endif>
    {{ $slot }}
</div>
