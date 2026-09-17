<?php

namespace Laramod\Tests\Feature;

use Illuminate\Support\ServiceProvider;
use Laramod\Facades\Modules;
use Laramod\LaramodServiceProvider;
use Laramod\ModuleRegistry;
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

    public function test_the_module_stubs_are_publishable(): void
    {
        $this->assertSame(
            [dirname(__DIR__, 2).'/src/../stubs/module' => base_path('stubs/laramod/module')],
            ServiceProvider::pathsToPublish(LaramodServiceProvider::class, 'laramod-stubs'),
        );
    }

    public function test_the_facade_resolves_the_shared_module_registry(): void
    {
        $this->assertSame($this->app->make(ModuleRegistry::class), Modules::getFacadeRoot());
    }
}
