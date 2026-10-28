<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Tests run against the database in .env, so refuse to start unless every connection is local.
     */
    protected function setUp(): void
    {
        parent::setUp();

        foreach (config('database.connections') as $name => $connection) {
            if (isset($connection['host']) && $connection['host'] !== '127.0.0.1') {
                $this->fail("Refusing to run tests: database connection [$name] host is {$connection['host']}, not 127.0.0.1.");
            }
        }
    }
}
