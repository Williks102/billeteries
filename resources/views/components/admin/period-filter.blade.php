@props(['action', 'period' => 'this_month'])
@php
    $periods = [
        'today' => "Aujourd'hui",
        'this_week' => 'Cette semaine',
        'this_month' => 'Ce mois',
        'last_month' => 'Mois dernier',
        'this_year' => 'Cette année',
        'last_year' => 'Année dernière',
    ];
@endphp
<form method="GET" action="{{ $action }}" class="row g-2 align-items-end mb-4">
    <div class="col-12 col-sm-6 col-md-4">
        <label for="period" class="form-label">Période</label>
        <select name="period" id="period" class="form-select">
            @foreach($periods as $value => $label)
                <option value="{{ $value }}" @selected($period === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12 col-sm-6 col-md-auto">
        <button type="submit" class="btn btn-primary w-100">Afficher</button>
    </div>
</form>
