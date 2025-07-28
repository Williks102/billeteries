{{-- resources/views/components/layout-switcher.blade.php --}}
{{-- Composant pour basculer automatiquement entre les layouts --}}

@php
    $user = auth()->user();
    $currentLayout = 'layouts.app'; // Layout par défaut

    if ($user) {
        $currentLayout = match($user->role) {
            'admin' => 'layouts.admin',
            'promoteur' => 'layouts.promoteur',
            'acheteur' => 'layouts.acheteur',
            default => 'layouts.app'
        };
    }
    
    // Override si spécifié explicitement
    if (isset($layout)) {
        $currentLayout = $layout;
    }
@endphp

@extends($currentLayout)

{{ $slot }}

{{-- ================================================================== --}}
{{-- resources/views/components/auto-sidebar.blade.php --}}
{{-- Composant pour afficher automatiquement la bonne sidebar --}}

@php
    $user = auth()->user();
@endphp

@if($user && $user->isAdmin())
    @include('partials.admin-sidebar')
@elseif($user && $user->isPromoteur())
    @include('partials.promoteur-sidebar')
@elseif($user && $user->isAcheteur())
    @include('partials.acheteur-sidebar')
@endif
