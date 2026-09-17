<?php

namespace Laramod\Tests\Feature;

use Illuminate\Support\ServiceProvider;
use Laramod\LaramodServiceProvider;
use Laramod\Tests\TestCase;

class ServiceProviderTest extends TestCase
{
    public function test_the_package_configuration_is_merged(): void
    {
        $this->assertSame('Modules', config('laramod.path'));
        $this->assertSame(['web'], config('laramod.routes.web.middleware'));
    }

    public function test_the_configuration_file_is_publishable(): void
    {
        $this->assertContains(
            config_path('laramod.php'),
            ServiceProvider::pathsToPublish(LaramodServiceProvider::class, 'laramod-config'),
        );
    }
}
