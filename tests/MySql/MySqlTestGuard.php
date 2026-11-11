<?php

namespace Tests\MySql;

/**
 * Garde S-4 du test MySQL, qui exécute `migrate:fresh` (destructif) : à ne jamais pouvoir viser une vraie base.
 */
final class MySqlTestGuard
{
    public const LOCAL_HOSTS = ['127.0.0.1', 'localhost', '::1'];

    /**
     * @return string|null motif du refus, ou null si la base peut être détruite
     */
    public static function refusal(string $database, string $host, string $appEnv, string $allowedHosts = ''): ?string
    {
        if (strtolower($appEnv) === 'production') {
            return 'APP_ENV=production : refus.';
        }
        if (!preg_match('/^[a-z0-9_]*_test$/', $database)) {
            return "le nom de base doit correspondre à ^[a-z0-9_]*_test$ (reçu : {$database}).";
        }
        $allowed = array_merge(self::LOCAL_HOSTS, array_filter(array_map(fn ($h) => strtolower(trim($h)), explode(',', $allowedHosts))));
        if (!in_array(strtolower(trim($host)), $allowed, true)) {
            return "hôte « {$host} » non local et absent de TEST_MYSQL_ALLOWED_HOSTS.";
        }

        return null;
    }
}
