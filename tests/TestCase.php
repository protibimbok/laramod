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
}
