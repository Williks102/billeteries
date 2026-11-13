<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * État de la base pour la page Sauvegardes. Ne renvoie jamais l'hôte, le nom d'utilisateur,
 * le nom de la base ni un mot de passe ; toute erreur donne null (affiché « indisponible »).
 */
class DatabaseInfo
{
    /** @return array{engine: string, version: ?string, size: ?string} */
    public function summary(): array
    {
        $driver = null;
        try {
            $driver = DB::connection()->getDriverName();
        } catch (\Throwable $e) {
            // ignoré : état « indisponible »
        }

        return [
            'engine' => match ($driver) {
                'mysql', 'mariadb' => 'MySQL / TiDB (compatible MySQL)',
                'sqlite' => 'SQLite',
                'pgsql' => 'PostgreSQL',
                default => 'Inconnu',
            },
            'version' => $this->version($driver),
            'size' => $this->size($driver),
        ];
    }

    private function version(?string $driver): ?string
    {
        try {
            $sql = $driver === 'sqlite' ? 'select sqlite_version() as v' : 'select version() as v';
            $v = DB::selectOne($sql)->v ?? null;

            return is_string($v) && $v !== '' ? $v : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function size(?string $driver): ?string
    {
        try {
            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                $bytes = DB::selectOne(
                    'select sum(data_length + index_length) as s from information_schema.tables where table_schema = ?',
                    [DB::connection()->getDatabaseName()]
                )->s ?? null;
            } elseif ($driver === 'sqlite') {
                $path = (string) DB::connection()->getDatabaseName();
                $bytes = ($path !== '' && $path !== ':memory:' && is_file($path)) ? filesize($path) : null;
            } else {
                $bytes = null;
            }

            return $bytes === null ? null : $this->format((float) $bytes);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function format(float $bytes): string
    {
        foreach (['octets', 'Ko', 'Mo', 'Go'] as $i => $unit) {
            if ($bytes < 1024 || $unit === 'Go') {
                return ($i === 0 ? (string) (int) $bytes : number_format($bytes, 1, ',', "\u{202F}")).' '.$unit;
            }
            $bytes /= 1024;
        }

        return '';
    }
}
