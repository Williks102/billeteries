<?php

namespace Tests\Feature;

use Symfony\Component\Yaml\Yaml;
use Tests\TestCase;

/** Garde-fous du déploiement de démo (Render + TiDB) : fichiers statiques uniquement, sans réseau ni Docker. */
class RenderDeploymentTest extends TestCase
{
    /** Clés qui ne doivent JAMAIS recevoir de valeur dans le dépôt. */
    private const SECRETS = ['APP_KEY', 'DB_PASSWORD', 'ADMIN_PASSWORD'];

    /** Variables d'infrastructure qui doivent exister à la fois dans .env.example et render.yaml. */
    private const SHARED = [
        'APP_NAME', 'APP_ENV', 'APP_KEY', 'APP_DEBUG', 'APP_URL', 'LOG_CHANNEL', 'LOG_LEVEL',
        'DB_CONNECTION', 'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD', 'DB_SSL_CA', 'DB_SSL_VERIFY',
        'SESSION_DRIVER', 'SESSION_SECURE_COOKIE', 'CACHE_STORE', 'QUEUE_CONNECTION',
        'MAIL_MAILER', 'TRUSTED_PROXIES', 'TELESCOPE_ENABLED', 'DEMO_SEED', 'ADMIN_EMAIL', 'ADMIN_PASSWORD',
        'DEFAULT_COMMISSION_RATE',
        'MANUAL_BANK_NAME', 'MANUAL_BANK_ACCOUNT_HOLDER', 'MANUAL_BANK_IBAN', 'MANUAL_MOBILE_MONEY_NUMBER',
        'MANUAL_MOBILE_MONEY_HOLDER', 'MANUAL_CASH_LOCATION',
    ];

    private function read(string $relative): string
    {
        $path = base_path($relative);
        $this->assertFileExists($path, $relative);

        return file_get_contents($path);
    }

    /** @return array<string, array<string, mixed>> variables de render.yaml indexées par nom */
    private function renderEnv(): array
    {
        $yaml = Yaml::parse($this->read('render.yaml'));
        $this->assertIsArray($yaml);
        $this->assertCount(1, $yaml['services']);

        $env = [];
        foreach ($yaml['services'][0]['envVars'] as $var) {
            $this->assertArrayHasKey('key', $var);
            $this->assertArrayNotHasKey($var['key'], $env, "Variable en double : {$var['key']}");
            $env[$var['key']] = $var;
        }

        return $env;
    }

    /** @return array<int, string> noms de variables de .env.example (lignes actives ou commentées `# NOM=`) */
    private function envExampleNames(): array
    {
        preg_match_all('/^#?\s*([A-Z][A-Z0-9_]+)=/m', $this->read('.env.example'), $m);

        return array_unique($m[1]);
    }

    public function test_render_yaml_is_valid_and_describes_a_free_docker_web_service(): void
    {
        $service = Yaml::parse($this->read('render.yaml'))['services'][0];

        $this->assertSame('web', $service['type']);
        $this->assertSame('docker', $service['runtime']);
        $this->assertSame('free', $service['plan']);
        $this->assertSame('frankfurt', $service['region']);
        $this->assertSame('/up', $service['healthCheckPath']);
        $this->assertSame('main', $service['branch']);
        $this->assertTrue($service['autoDeploy']);
    }

    public function test_every_sync_false_variable_has_no_value(): void
    {
        $count = 0;
        foreach ($this->renderEnv() as $key => $var) {
            if (($var['sync'] ?? null) === false) {
                $count++;
                $this->assertArrayNotHasKey('value', $var, "$key est sync: false et ne doit avoir aucune valeur");
                $this->assertArrayNotHasKey('generateValue', $var, $key);
            } else {
                $this->assertArrayHasKey('value', $var, "$key doit avoir une valeur explicite");
            }
        }
        $this->assertGreaterThan(8, $count);
    }

    public function test_secrets_are_sync_false_in_render_yaml(): void
    {
        $env = $this->renderEnv();
        foreach (array_merge(self::SECRETS, ['APP_URL', 'DB_HOST', 'DB_DATABASE', 'DB_USERNAME', 'ADMIN_EMAIL']) as $key) {
            $this->assertArrayHasKey($key, $env, $key);
            $this->assertFalse($env[$key]['sync'] ?? null, "$key doit être sync: false");
        }
    }

    public function test_render_yaml_safe_values(): void
    {
        $v = fn (string $k) => $this->renderEnv()[$k]['value'] ?? null;

        $this->assertSame('production', $v('APP_ENV'));
        $this->assertSame('false', $v('APP_DEBUG'));
        $this->assertSame('mysql', $v('DB_CONNECTION'));
        $this->assertSame('4000', $v('DB_PORT'));
        $this->assertSame('/etc/ssl/certs/ca-certificates.crt', $v('DB_SSL_CA'));
        $this->assertSame('sync', $v('QUEUE_CONNECTION'));
        $this->assertSame('log', $v('MAIL_MAILER'));
        $this->assertSame('true', $v('SESSION_SECURE_COOKIE'));
        $this->assertSame('false', $v('TELESCOPE_ENABLED'));
        $this->assertSame('6', $v('DEFAULT_COMMISSION_RATE'));
        $this->assertSame('*', $v('TRUSTED_PROXIES'));
        $this->assertSame('stderr', $v('LOG_CHANNEL'));
    }

    public function test_no_secret_key_has_a_value_in_deployment_files(): void
    {
        foreach (['render.yaml', '.env.example', 'Dockerfile', 'docker/entrypoint.sh', 'docs/deploiement-render.md'] as $file) {
            foreach (self::SECRETS as $key) {
                // `CLE=valeur` ou `CLE: valeur` ou `value:` rattaché à la clé, hors commentaire et hors références ${CLE}
                $this->assertDoesNotMatchRegularExpression(
                    '/^[ \t]*(?!#)(?:export\s+)?'.$key.'[ \t]*[=:][ \t]*["\']?[^\s"\'#$<`(][^\s]*/m',
                    $this->read($file),
                    "$file contient une valeur pour $key"
                );
            }
        }

        // render.yaml : une clé secrète n'est jamais suivie d'une ligne `value:`
        foreach (self::SECRETS as $key) {
            $this->assertDoesNotMatchRegularExpression('/key:\s*'.$key.'\b[^\n]*\n\s*value:/', $this->read('render.yaml'));
        }
    }

    public function test_dockerfile_installs_without_dev_dependencies_and_without_secrets(): void
    {
        $dockerfile = $this->read('Dockerfile');

        $this->assertStringContainsString('composer install --no-dev', $dockerfile);
        $this->assertStringContainsString('composer dump-autoload --no-dev', $dockerfile);
        $this->assertStringContainsString('php:8.3', $dockerfile);
        $this->assertStringContainsString('install-php-extensions gd', $dockerfile);
        foreach (['pdo_mysql', 'bcmath', 'zip', 'intl', 'exif', 'gd'] as $extension) {
            $this->assertStringContainsString($extension, $dockerfile);
        }
        // pas d'identifiants Composer ni d'authentification
        $this->assertDoesNotMatchRegularExpression('/COMPOSER_AUTH|http-basic|github-oauth|auth\.json|--password|--token/i', $dockerfile);
        $this->assertDoesNotMatchRegularExpression('/^\s*(ENV|ARG)\s+(APP_KEY|DB_PASSWORD|ADMIN_PASSWORD|DB_USERNAME)\b/mi', $dockerfile);
        // les caches générés avec les paquets de dev (Telescope, Debugbar) ne sont pas embarqués
        $this->assertStringContainsString('rm -f bootstrap/cache/*.php', $dockerfile);
        $this->assertStringContainsString('ENTRYPOINT', $dockerfile);

        $ignore = $this->read('.dockerignore');
        foreach (['.env', 'vendor', 'node_modules', '.git', 'bootstrap/cache/*.php'] as $entry) {
            $this->assertMatchesRegularExpression('/^'.preg_quote($entry, '/').'$/m', $ignore, $entry);
        }
    }

    public function test_entrypoint_runs_required_steps_in_order_and_is_valid_shell(): void
    {
        $path = base_path('docker/entrypoint.sh');
        $script = $this->read('docker/entrypoint.sh');

        $order = ['art migrate --force', 'storage:link --force', 'art demo:seed', 'art config:cache', 'art route:cache', 'art view:cache', 'php artisan schedule:work', 'apache2-foreground &'];
        $last = -1;
        foreach ($order as $needle) {
            $pos = strpos($script, $needle);
            $this->assertNotFalse($pos, "entrypoint : $needle manquant");
            $this->assertGreaterThan($last, $pos, "entrypoint : $needle hors de l'ordre attendu");
            $last = $pos;
        }

        $this->assertStringContainsString('DEMO_SEED', $script);
        $this->assertStringContainsString('wait -n', $script);
        $this->assertStringNotContainsString('migrate --force || true', $script);
        $this->assertStringNotContainsString('--seed', $script);
        $this->assertStringNotContainsString('db:seed', $script, "l'entrypoint ne doit lancer que demo:seed");

        if (PHP_OS_FAMILY !== 'Windows') {
            exec('sh -n '.escapeshellarg($path).' 2>&1', $out, $code);
            $this->assertSame(0, $code, implode("\n", $out));
        }
    }

    public function test_demo_seed_command_enforces_a_minimum_password_length_of_12(): void
    {
        $this->assertSame(12, \App\Console\Commands\DemoSeed::MIN_PASSWORD_LENGTH);
    }

    public function test_env_example_and_render_yaml_share_the_same_infrastructure_variables(): void
    {
        $example = $this->envExampleNames();
        $render = array_keys($this->renderEnv());

        foreach (self::SHARED as $name) {
            $this->assertContains($name, $example, ".env.example ne référence pas $name");
            $this->assertContains($name, $render, "render.yaml ne référence pas $name");
        }

        // toute variable de render.yaml existe aussi dans .env.example (aucune dérive)
        $this->assertSame([], array_values(array_diff($render, $example)), 'Variables de render.yaml absentes de .env.example');
    }

    public function test_env_example_has_no_secret_values_and_safe_defaults(): void
    {
        $example = $this->read('.env.example');

        foreach (['APP_KEY', 'DB_PASSWORD', 'ADMIN_PASSWORD', 'DB_SSL_CA', 'DB_SSL_VERIFY', 'ADMIN_EMAIL', 'TRUSTED_PROXIES'] as $key) {
            $this->assertMatchesRegularExpression('/^(#\s*)?'.$key.'=\s*$/m', $example, "$key doit rester vide dans .env.example");
        }
        $this->assertMatchesRegularExpression('/^DEMO_SEED=false$/m', $example);
        $this->assertMatchesRegularExpression('/^TELESCOPE_ENABLED=false$/m', $example);
    }

    public function test_database_config_reads_ssl_options_from_environment(): void
    {
        $config = $this->read('config/database.php');

        $this->assertStringContainsString("env('DB_SSL_CA')", $config);
        $this->assertStringContainsString('PDO::MYSQL_ATTR_SSL_CA', $config);
        $this->assertStringContainsString("env('DB_SSL_VERIFY')", $config);
        $this->assertStringContainsString('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT', $config);

        // sans variable : aucune option SSL (connexions locales inchangées)
        $this->assertSame([], array_intersect_key(
            config('database.connections.mysql.options'),
            [\PDO::MYSQL_ATTR_SSL_CA => 1, \PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => 1]
        ));
    }

    /** Mineur (relecteur) : la mutation DB_SSL_VERIFY true -> false restait verte ; on recharge réellement la config. */
    public function test_database_config_maps_ssl_environment_to_pdo_options(): void
    {
        $this->assertTrue(extension_loaded('pdo_mysql'), 'pdo_mysql requis pour ce test');

        $cases = [
            ['true', true],
            ['1', true],
            ['false', false],
            ['0', false],
        ];

        foreach ($cases as [$raw, $expected]) {
            foreach (['DB_SSL_CA' => '/etc/ssl/certs/ca-certificates.crt', 'DB_SSL_VERIFY' => $raw] as $key => $value) {
                $_ENV[$key] = $_SERVER[$key] = $value;
            }

            try {
                $config = require base_path('config/database.php');
                $options = $config['connections']['mysql']['options'];

                $this->assertSame('/etc/ssl/certs/ca-certificates.crt', $options[\PDO::MYSQL_ATTR_SSL_CA]);
                $this->assertSame($expected, $options[\PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT], "DB_SSL_VERIFY=$raw");
            } finally {
                unset($_ENV['DB_SSL_CA'], $_ENV['DB_SSL_VERIFY'], $_SERVER['DB_SSL_CA'], $_SERVER['DB_SSL_VERIFY']);
            }
        }
    }

    public function test_dockerfile_is_multistage_pinned_and_without_obsolete_packages(): void
    {
        $dockerfile = $this->read('Dockerfile');

        // I-trixie : libfreetype6-dev n'existe plus sur Debian 13 ; on n'utilise plus ce nom (ni la base « flottante »).
        $this->assertStringNotContainsString('libfreetype6-dev', $dockerfile);
        $this->assertDoesNotMatchRegularExpression('/^FROM php:8\.3-apache\s*$/m', $dockerfile, 'Base non épinglée');
        $this->assertMatchesRegularExpression('/^FROM php:8\.3-apache-bookworm$/m', $dockerfile);

        // F1 : multi-stage ; composer, git et unzip ne sont pas dans l'étape finale.
        $this->assertGreaterThanOrEqual(2, preg_match_all('/^FROM /m', $dockerfile));
        $final = substr($dockerfile, strrpos($dockerfile, "\nFROM ") + 1);
        $this->assertStringNotContainsString('composer', preg_replace('/^\s*#.*$/m', '', $final), 'composer ne doit pas être dans l\'image finale');
        $this->assertDoesNotMatchRegularExpression('/\b(git|unzip)\b/', preg_replace('/^\s*#.*$/m', '', $final));
        $this->assertStringContainsString('--no-dev', $dockerfile);
    }

    public function test_entrypoint_normalizes_app_key_without_printing_it(): void
    {
        $script = $this->read('docker/entrypoint.sh');

        $this->assertStringContainsString('app:normalize-key --print', $script);
        $this->assertStringContainsString('export APP_KEY=', $script);
        // la valeur n'est jamais passée à echo/log
        $this->assertDoesNotMatchRegularExpression('/(echo|log)[^\n]*\$\{?(NORMALIZED_APP_KEY|APP_KEY)/', $script);
    }

    public function test_apache_config_hardens_uploads_and_static_headers(): void
    {
        $conf = $this->read('docker/apache-render.conf');

        $this->assertStringContainsString('/var/www/html/public/storage', $conf);
        $this->assertStringContainsString('php_admin_flag engine off', $conf);
        $this->assertStringContainsString('X-Content-Type-Options', $conf);
        $this->assertStringContainsString('Strict-Transport-Security', $conf);
        // disable_functions est PHP_INI_SYSTEM : inopérante dans la config Apache, elle vit dans php.ini
        $this->assertDoesNotMatchRegularExpression('/^\s*php_admin_value\s+disable_functions/m', $conf);
        $ini = $this->read('docker/php.ini');
        $this->assertMatchesRegularExpression('/^disable_functions=[^\n]*shell_exec/m', $ini);
        // exec (backup admin) et proc_open (schedule:work, Symfony Process) restent utilisables
        preg_match('/^disable_functions=(.*)$/m', $ini, $m);
        $this->assertNotContains('exec', explode(',', $m[1]));
        $this->assertNotContains('proc_open', explode(',', $m[1]));
    }

    public function test_no_provider_references_dev_only_packages_unconditionally(): void
    {
        $providers = $this->read('bootstrap/providers.php');
        $this->assertStringNotContainsString('Telescope', $providers);
        $this->assertStringNotContainsString('Debugbar', $providers);
    }

    public function test_deployment_guide_exists_and_covers_key_topics(): void
    {
        $doc = $this->read('docs/deploiement-render.md');

        foreach (['TiDB', 'APP_KEY', 'key:generate --show', 'DB_SSL_CA', 'TRUSTED_PROXIES', 'ENUM', '419', 'éphémère', 'test:mysql'] as $needle) {
            $this->assertStringContainsString($needle, $doc, "guide : $needle manquant");
        }
    }
}
