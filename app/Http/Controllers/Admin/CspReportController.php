<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Stats\CspViolationReport;

/** Écran « Rapports CSP » : lecture seule, réservé aux admins (middleware du groupe de routes). */
class CspReportController extends Controller
{
    public function index(CspViolationReport $report)
    {
        return view('admin.csp.index', [
            'report' => $report->rows(),
            'reportOnly' => (bool) config('security.csp_report_only', true),
        ]);
    }
}
