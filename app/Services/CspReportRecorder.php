<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

/**
 * Enregistre les rapports de violation CSP, agrégés, SANS donnée personnelle.
 *
 * Conservé : directive violée, ressource bloquée (hôte seulement, ou inline / eval / data / blob), modèle de route
 * de la page (sans query string, fragment ni valeur de paramètre), compteur, première et dernière date.
 * JAMAIS conservé : échantillon de script, fichier source, referrer, user-agent, IP, en-têtes.
 *
 * Ne lève jamais : l'appelant répond 204 quoi qu'il arrive.
 */
class CspReportRecorder
{
    public const MAX_BODY_BYTES = 16384;

    /** Plafond global d'écritures par minute (toutes IP confondues). */
    public const GLOBAL_PER_MINUTE = 120;

    /** Rapports lus par requête (un rapport « reports+json » peut en contenir plusieurs). */
    private const MAX_REPORTS_PER_REQUEST = 10;

    public function handle(Request $request): void
    {
        try {
            $declared = (int) $request->headers->get('Content-Length', 0);
            if ($declared > self::MAX_BODY_BYTES) {
                return;
            }

            $type = strtolower((string) $request->headers->get('Content-Type'));
            if (! str_starts_with($type, 'application/csp-report') && ! str_starts_with($type, 'application/reports+json')) {
                return;
            }

            // Lecture bornée : jamais plus de MAX_BODY_BYTES + 1 octet en mémoire.
            $stream = $request->getContent(true);
            $body = is_resource($stream) ? (string) stream_get_contents($stream, self::MAX_BODY_BYTES + 1) : '';
            if ($body === '' || strlen($body) > self::MAX_BODY_BYTES) {
                return;
            }

            $decoded = json_decode($body, true, 8);
            if (! is_array($decoded)) {
                return;
            }

            $reports = array_slice($this->extractReports($decoded), 0, self::MAX_REPORTS_PER_REQUEST);
            foreach ($reports as $report) {
                $this->store($report);
            }
        } catch (\Throwable $e) {
            try {
                Log::warning('Rapport CSP : enregistrement impossible', ['exception' => get_class($e)]);
            } catch (\Throwable) {
                // rien : ne jamais casser la requête
            }
        }
    }

    /** @return list<array{directive:string, blocked:string, path:string}> */
    private function extractReports(array $decoded): array
    {
        $out = [];

        // Ancien format (application/csp-report) : {"csp-report": {...}}
        if (isset($decoded['csp-report']) && is_array($decoded['csp-report'])) {
            $r = $decoded['csp-report'];
            $out[] = [
                'directive' => $r['effective-directive'] ?? $r['violated-directive'] ?? null,
                'blocked' => $r['blocked-uri'] ?? '',
                'document' => $r['document-uri'] ?? '',
            ];
        } else {
            // Reporting API (application/reports+json) : liste de {"type":"csp-violation","body":{...}}
            $list = array_is_list($decoded) ? $decoded : [$decoded];
            foreach ($list as $item) {
                if (! is_array($item) || ($item['type'] ?? null) !== 'csp-violation' || ! is_array($item['body'] ?? null)) {
                    continue;
                }
                $b = $item['body'];
                $out[] = [
                    'directive' => $b['effectiveDirective'] ?? $b['violatedDirective'] ?? null,
                    'blocked' => $b['blockedURL'] ?? ($b['blockedURI'] ?? ''),
                    'document' => $b['documentURL'] ?? ($item['url'] ?? ''),
                ];
            }
        }

        $normalized = [];
        foreach ($out as $r) {
            $normalized[] = [
                'directive' => $this->directive($r['directive']),
                'blocked' => $this->blocked($r['blocked']),
                'path' => $this->documentPath($r['document']),
            ];
        }

        return $normalized;
    }

    private function directive(mixed $value): string
    {
        $first = strtolower(trim(explode(' ', is_string($value) ? trim($value) : '')[0]));

        return preg_match('/^[a-z-]{1,40}$/', $first) === 1 ? $first : 'inconnue';
    }

    /** Hôte seulement, ou inline / eval / data / blob ; jamais de chemin, de query string ni d'échantillon. */
    private function blocked(mixed $value): string
    {
        $value = is_string($value) ? strtolower(trim($value)) : '';
        if ($value === '' || $value === 'inline') {
            return 'inline';
        }
        if ($value === 'eval' || $value === 'wasm-eval') {
            return 'eval';
        }
        if (str_starts_with($value, 'data:') || $value === 'data') {
            return 'data';
        }
        if (str_starts_with($value, 'blob:') || $value === 'blob') {
            return 'blob';
        }

        $host = parse_url($value, PHP_URL_HOST);
        if (! is_string($host) || preg_match('/^[a-z0-9.\-]{1,100}$/', $host) !== 1) {
            return 'autre';
        }

        return $host;
    }

    /**
     * Modèle de route de la page (ex. /events/{event}) : ni query string, ni fragment, ni valeur de paramètre
     * (un code de billet ou un jeton dans l'URL n'est donc jamais conservé). Page inconnue : « /(inconnue) ».
     */
    private function documentPath(mixed $value): string
    {
        $path = is_string($value) ? parse_url($value, PHP_URL_PATH) : null;
        if (! is_string($path) || $path === '' || $path[0] !== '/') {
            return '/(inconnue)';
        }

        try {
            $route = Route::getRoutes()->match(Request::create(substr($path, 0, 300), 'GET'));
        } catch (\Throwable) {
            return '/(inconnue)';
        }

        return mb_substr('/'.ltrim($route->uri(), '/'), 0, 150);
    }

    /** @param array{directive:string, blocked:string, path:string} $report */
    private function store(array $report): void
    {
        // Plafond global par minute : au-delà, on ignore (anti-inondation).
        if (! RateLimiter::attempt('csp-report:global', self::GLOBAL_PER_MINUTE, fn () => true, 60)) {
            return;
        }

        $hash = hash('sha256', $report['directive']."\n".$report['blocked']."\n".$report['path']);
        $now = now();
        $table = DB::table('csp_violations');

        $increment = fn () => DB::table('csp_violations')->where('key_hash', $hash)
            ->update(['hits' => DB::raw('hits + 1'), 'last_seen_at' => $now]);

        if ($increment() > 0) {
            return;
        }

        // Nouvelle clé : seulement sous le plafond de lignes ; au-delà on n'incrémente que l'existant.
        if ($table->count() >= (int) config('security.csp_report_max_rows', 500)) {
            return;
        }

        $inserted = DB::table('csp_violations')->insertOrIgnore([
            'key_hash' => $hash,
            'directive' => $report['directive'],
            'blocked' => $report['blocked'],
            'document_path' => $report['path'],
            'hits' => 1,
            'first_seen_at' => $now,
            'last_seen_at' => $now,
        ]);

        if ($inserted === 0) { // insertion concurrente de la même clé : on compte quand même
            $increment();
        }
    }
}
