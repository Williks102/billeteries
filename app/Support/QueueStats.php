<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Compteurs de la file d'attente pour la page admin Emails (lecture seule). Valeur non calculable (table absente,
 * base indisponible) = null, affiché « — » : jamais d'exception.
 */
class QueueStats
{
    /** @return array{pending: ?int, failed: ?int} */
    public static function counts(): array
    {
        return [
            'pending' => self::count((string) config('queue.connections.database.table', 'jobs')),
            'failed' => self::count((string) config('queue.failed.table', 'failed_jobs')),
        ];
    }

    private static function count(string $table): ?int
    {
        try {
            if (! Schema::hasTable($table)) {
                return null;
            }

            return (int) DB::table($table)->count();
        } catch (\Throwable) {
            return null;
        }
    }
}
