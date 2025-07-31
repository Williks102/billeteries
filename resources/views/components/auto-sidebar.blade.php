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