@props(['label', 'value', 'hint' => null])
<div class="card h-100">
    <div class="card-body">
        <div class="text-muted small">{{ $label }}</div>
        <div class="stat-value fw-bold">{{ $value }}</div>
        @if($hint)
            <div class="text-muted small mt-1">{{ $hint }}</div>
        @endif
    </div>
</div>
