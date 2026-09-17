<?php

namespace Laramod\Tests;

use Laramod\LaramodServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Get the package providers.
     *
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [LaramodServiceProvider::class];
    }

    /**
     * Define the environment every test runs in.
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
    }
}
