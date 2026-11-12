{{-- Alias historique : délègue à <x-stat-card> (les valeurs déjà formatées en texte sont affichées telles quelles). --}}
@props(['label', 'value' => null, 'hint' => null])
<x-stat-card :label="$label" :value="$value" :hint="$hint" />
