<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tests\MySql\MySqlTestGuard;

class MySqlTestGuardTest extends TestCase
{
    public function test_accepts_a_local_dedicated_test_database(): void
    {
        $this->assertNull(MySqlTestGuard::refusal('billeterie_test', '127.0.0.1', 'testing'));
        $this->assertNull(MySqlTestGuard::refusal('billeterie_test', 'localhost', ''));
    }

    public function test_refuses_names_that_merely_contain_test(): void
    {
        foreach (['latest', 'contest_prod', 'billeterie', 'prod_test_x', 'Billeterie_TEST', 'a-b_test', ''] as $name) {
            $this->assertNotNull(MySqlTestGuard::refusal($name, '127.0.0.1', 'testing'), $name);
        }
    }

    public function test_refuses_remote_hosts_unless_explicitly_allowed(): void
    {
        $this->assertNotNull(MySqlTestGuard::refusal('billeterie_test', 'db.prod.example.com', 'testing'));
        $this->assertNotNull(MySqlTestGuard::refusal('billeterie_test', 'db.prod.example.com', 'testing', 'other.example.com'));
        $this->assertNull(MySqlTestGuard::refusal('billeterie_test', 'ci-db.example.com', 'testing', 'x.example.com, ci-db.example.com'));
    }

    public function test_refuses_production_even_for_a_perfect_name_and_host(): void
    {
        $this->assertNotNull(MySqlTestGuard::refusal('billeterie_test', '127.0.0.1', 'production'));
        $this->assertNotNull(MySqlTestGuard::refusal('billeterie_test', '127.0.0.1', 'Production'));
    }

    public function test_refuses_a_trailing_newline_in_the_database_name(): void
    {
        // S-8 : `$` PCRE accepte « \n » final ; la garde utilise \z.
        $this->assertSame(1, preg_match('/^[a-z0-9_]*_test$/', "db_test\n"), 'prérequis : $ accepte le \n final');
        $this->assertNotNull(MySqlTestGuard::refusal("billeterie_test\n", '127.0.0.1', 'testing'));
    }

    public function test_refuses_when_any_connection_url_is_defined(): void
    {
        foreach (['mysql://u:p@prod.example.com/prod', 'x'] as $url) {
            $this->assertNotNull(MySqlTestGuard::refusal('billeterie_test', '127.0.0.1', 'testing', '', [$url]));
        }
        $this->assertNull(MySqlTestGuard::refusal('billeterie_test', '127.0.0.1', 'testing', '', [null, '']));
        $this->assertSame(['DB_URL', 'DATABASE_URL', 'MYSQL_URL'], MySqlTestGuard::URL_ENV_VARS);
    }

    public function test_the_url_really_overrides_host_and_database_in_laravel_config(): void
    {
        // Preuve du risque S-8 : après parsing de l'URL (comme ConnectionFactory), host et base viennent de l'URL.
        $parsed = (new \Illuminate\Support\ConfigurationUrlParser())->parseConfiguration([
            'driver' => 'mysql', 'url' => 'mysql://u:p@prod.example.com/vraie_base', 'host' => '127.0.0.1', 'database' => 'billeterie_test',
        ]);
        $this->assertSame('prod.example.com', $parsed['host']);
        $this->assertSame('vraie_base', $parsed['database']);
        // La garde, rejouée sur la configuration effective (url conservée), refuse.
        $this->assertNotNull(MySqlTestGuard::refusal($parsed['database'], $parsed['host'], 'testing'));
    }

    public function test_refuses_look_alike_hosts(): void
    {
        foreach (['127.0.0.1.evil.com', 'localhost.', 'localhost ', ' 127.0.0.1', 'localhost.evil.com', '127.0.0.1:3306'] as $host) {
            $this->assertNotNull(MySqlTestGuard::refusal('billeterie_test', $host, 'testing'), $host);
        }
    }

    public function test_refuses_production_database_names(): void
    {
        foreach (['contest_prod', 'prod', 'billeterie_test_prod', 'production'] as $name) {
            $this->assertNotNull(MySqlTestGuard::refusal($name, '127.0.0.1', 'testing'), $name);
        }
    }

    public function test_env_urls_detects_the_process_environment(): void
    {
        $saved = getenv('MYSQL_URL');
        putenv('MYSQL_URL=mysql://u:p@prod.example.com/prod');
        try {
            $this->assertContains('mysql://u:p@prod.example.com/prod', MySqlTestGuard::envUrls());
        } finally {
            $saved === false ? putenv('MYSQL_URL') : putenv('MYSQL_URL=' . $saved);
        }
        $this->assertNotContains('mysql://u:p@prod.example.com/prod', MySqlTestGuard::envUrls());
    }
}
