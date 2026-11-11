<?php

namespace Tests\MySql;

/**
 * Garde S-4 / S-8 du test MySQL, qui exécute `migrate:fresh` (destructif) : à ne jamais pouvoir viser une vraie base.
 *
 * S-8 : config/database.php lit DB_URL, qui SURCHARGE host et base de la connexion. Toute URL de connexion
 * (DB_URL, DATABASE_URL, MYSQL_URL) est donc refusée, et la garde est rejouée sur la connexion RÉELLEMENT utilisée
 * (voir EventPendingCapConcurrencyTest::setUp), pas seulement sur les variables d'environnement lues.
 */
final class MySqlTestGuard
{
    public const LOCAL_HOSTS = ['127.0.0.1', 'localhost', '::1'];

    /** Variables d'environnement qui peuvent surcharger la connexion par une URL. */
    public const URL_ENV_VARS = ['DB_URL', 'DATABASE_URL', 'MYSQL_URL'];

    /**
     * @param  array<int, mixed>  $urls  valeurs d'URL de connexion éventuelles (env + config) : toute valeur non vide refuse
     * @return string|null motif du refus, ou null si la base peut être détruite
     */
    public static function refusal(string $database, string $host, string $appEnv, string $allowedHosts = '', array $urls = []): ?string
    {
        foreach ($urls as $url) {
            if ($url !== null && $url !== '' && $url !== false) {
                return 'une URL de connexion (DB_URL / DATABASE_URL / MYSQL_URL) est définie : elle surcharge hôte et base, refus.';
            }
        }
        if (strtolower(trim($appEnv)) === 'production') {
            return 'APP_ENV=production : refus.';
        }
        // \z (fin ABSOLUE de chaîne) : `$` accepterait un « \n » final.
        if (!preg_match('/\A[a-z0-9_]*_test\z/', $database)) {
            return 'le nom de base doit correspondre à ^[a-z0-9_]*_test$ (reçu : ' . addcslashes($database, "\0..\37") . ').';
        }
        $allowed = array_merge(self::LOCAL_HOSTS, array_filter(array_map(fn ($h) => strtolower(trim($h)), explode(',', $allowedHosts))));
        // Comparaison stricte, sans trim : « localhost. » ou « 127.0.0.1.evil.com » ne sont pas locaux.
        if (!in_array(strtolower($host), $allowed, true)) {
            return "hôte « {$host} » non local et absent de TEST_MYSQL_ALLOWED_HOSTS.";
        }

        return null;
    }

    /**
     * URLs de connexion présentes dans l'environnement du processus.
     *
     * @return array<int, string>
     */
    public static function envUrls(): array
    {
        $urls = [];
        foreach (self::URL_ENV_VARS as $name) {
            $value = getenv($name);
            if ($value !== false && $value !== '') {
                $urls[] = $value;
            }
        }

        return $urls;
    }
}
