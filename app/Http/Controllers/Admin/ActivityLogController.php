<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ResolvesStatsPeriod;
use App\Http\Controllers\Controller;
use App\Services\Stats\ActivityLogQuery;
use Illuminate\Http\Request;

/** Journal d'activité de la plateforme : lecture seule, réservé aux admins (middleware du groupe de routes). */
class ActivityLogController extends Controller
{
    use ResolvesStatsPeriod;

    public function index(Request $request, ActivityLogQuery $query)
    {
        $period = $this->statsPeriod($request, 'admin.activity.index');

        $filters = [
            'family' => is_string($request->query('family')) ? $request->query('family') : null,
            'actor' => is_string($request->query('actor')) ? $request->query('actor') : null,
        ];

        return view('admin.activity.index', [
            'logs' => $query->paginate($filters, $period),
            'period' => $period,
            'families' => $query->families(),
            'actors' => $query->actors(),
            'filters' => $filters,
        ]);
    }
}
