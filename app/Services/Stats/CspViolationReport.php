<?php

namespace App\Services\Stats;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Données de l'écran admin « Rapports CSP » (lecture seule). Table absente ou illisible : liste vide, sans exception. */
class CspViolationReport
{
    private const LIMIT = 500;

    /**
     * @return array{available:bool, rows:list<array{directive:string, blocked:string, path:string, hits:int, last_seen:string}>, total_hits:int}
     */
    public function rows(): array
    {
        try {
            if (! Schema::hasTable('csp_violations')) {
                return ['available' => false, 'rows' => [], 'total_hits' => 0];
            }

            $rows = DB::table('csp_violations')->orderByDesc('hits')->orderByDesc('last_seen_at')->limit(self::LIMIT)->get()
                ->map(fn ($r) => [
                    'directive' => (string) $r->directive,
                    'blocked' => (string) $r->blocked,
                    'path' => (string) $r->document_path,
                    'hits' => (int) $r->hits,
                    'last_seen' => $r->last_seen_at ? \Illuminate\Support\Carbon::parse($r->last_seen_at)->timezone('Africa/Abidjan')->format('d/m/Y H:i') : '—',
                ])->all();

            return ['available' => true, 'rows' => $rows, 'total_hits' => array_sum(array_column($rows, 'hits'))];
        } catch (\Throwable) {
            return ['available' => false, 'rows' => [], 'total_hits' => 0];
        }
    }
}
