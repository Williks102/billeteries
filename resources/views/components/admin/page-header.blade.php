@props(['title', 'subtitle' => null, 'back' => null, 'backLabel' => 'Retour'])
<x-admin.mobile-styles />
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
    <div>
        <h1 class="h3 mb-1">{{ $title }}</h1>
        @if($subtitle)
            <p class="text-muted mb-0">{{ $subtitle }}</p>
        @endif
    </div>
    <div class="d-flex flex-wrap gap-2">
        {{ $slot }}
        @if($back)
            <a href="{{ $back }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left" aria-hidden="true"></i> {{ $backLabel }}
            </a>
        @endif
    </div>
</div>
