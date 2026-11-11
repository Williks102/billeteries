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
}
