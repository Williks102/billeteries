<?php

namespace App\Http\Controllers;

use App\Services\CspReportRecorder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** Réception des rapports CSP du navigateur : public, sans CSRF, limité en débit, répond toujours 204. */
class CspReportController extends Controller
{
    public function store(Request $request, CspReportRecorder $recorder): Response
    {
        $recorder->handle($request);

        return response()->noContent();
    }
}
