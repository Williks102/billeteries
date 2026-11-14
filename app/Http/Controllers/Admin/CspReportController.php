<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Stats\CspViolationReport;

/** Écran « Rapports CSP » : réservé aux admins (middleware du groupe de routes). Lecture, et remise à zéro des compteurs. */
class CspReportController extends Controller
{
    public function index(CspViolationReport $report)
    {
        return view('admin.csp.index', [
            'report' => $report->rows(),
            'reportOnly' => (bool) config('security.csp_report_only', true),
        ]);
    }

    /** Remet les compteurs à zéro (après une série de corrections, ou si des lignes parasites occupent le plafond). */
    public function clear()
    {
        try {
            \Illuminate\Support\Facades\DB::table('csp_violations')->delete();
        } catch (\Throwable $e) {
            return back()->with('error', 'Impossible de vider les rapports.');
        }

        return back()->with('success', 'Rapports CSP remis à zéro.');
    }
}
