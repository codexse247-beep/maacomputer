<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        // The live server caches its config (MySQL). Tests would then ignore phpunit.xml and
        // RefreshDatabase would aim at the real database — refuse to run instead.
        if (is_file(dirname(__DIR__).'/bootstrap/cache/config.php')) {
            fwrite(STDERR, "\nConfig is cached (live settings). Run `php artisan optimize:clear` before testing, then `php artisan optimize` after.\n");
            exit(1);
        }

        parent::setUp();
    }
}
