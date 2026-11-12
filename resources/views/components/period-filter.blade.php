{{--
    Filtre de période aligné sur App\Support\Stats\DatePeriod::from(Request) : presets + dates personnalisées.
    Props : action (URL), period (DatePeriod actif), keep (autres paramètres de requête à conserver).
    Affiche TOUJOURS la période active (libellé) ; l'erreur de validation « period » s'affiche sous le formulaire.
--}}
@props(['action', 'period', 'keep' => []])
@php
    $labels = [
        'today' => "Aujourd'hui", '7d' => '7 derniers jours', '30d' => '30 derniers jours', 'this_month' => 'Ce mois-ci',
        'last_month' => 'Mois dernier', 'this_year' => 'Cette année', 'all' => 'Toute la période', 'custom' => 'Dates personnalisées',
    ];
    $uid = 'pf-'.\Illuminate\Support\Str::random(6);
    $isCustom = $period->key === 'custom';
@endphp
<form method="GET" action="{{ $action }}" class="period-filter row g-2 align-items-end mb-2" id="{{ $uid }}">
    @foreach($keep as $k => $v)
        @if(is_scalar($v) && $v !== '')
            <input type="hidden" name="{{ $k }}" value="{{ $v }}">
        @endif
    @endforeach
    <div class="col-12 col-sm-6 col-md-3">
        <label for="{{ $uid }}-period" class="form-label mb-1">Période</label>
        <select name="period" id="{{ $uid }}-period" class="form-select">
            @foreach($labels as $key => $label)
                <option value="{{ $key }}" @selected($period->key === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-6 col-md-3">
        <label for="{{ $uid }}-start" class="form-label mb-1">Du</label>
        <input type="date" class="form-control" id="{{ $uid }}-start" name="start_date" value="{{ $isCustom ? $period->start->format('Y-m-d') : '' }}">
    </div>
    <div class="col-6 col-md-3">
        <label for="{{ $uid }}-end" class="form-label mb-1">Au</label>
        <input type="date" class="form-control" id="{{ $uid }}-end" name="end_date" value="{{ $isCustom ? $period->end->format('Y-m-d') : '' }}">
    </div>
    <div class="col-12 col-md-3">
        <button type="submit" class="btn btn-primary w-100">Afficher</button>
    </div>
</form>
@error('period')
    <div class="alert alert-danger py-2" role="alert">{{ $message }}</div>
@enderror
<p class="period-active" data-period-active>Période affichée : <strong>{{ $period->label }}</strong></p>
<script>
    (function () {
        var f = document.getElementById(@json($uid));
        if (!f) return;
        var sel = f.elements['period'], s = f.elements['start_date'], e = f.elements['end_date'];
        sel.addEventListener('change', function () { if (sel.value !== 'custom') { s.value = ''; e.value = ''; } });
        [s, e].forEach(function (i) { i.addEventListener('input', function () { sel.value = 'custom'; }); });
    })();
</script>
