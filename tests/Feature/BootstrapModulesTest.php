<?php

namespace Laramod\Tests\Feature;

use Laramod\Modules;
use Laramod\Tests\Fixtures\Modules\Blog\BlogModule;
use Laramod\Tests\TestCase;

class BootstrapModulesTest extends TestCase
{
    /**
     * Point the application at the fixture bootstrap directory before the provider is registered.
     */
    protected function resolveApplication()
    {
        return tap(parent::resolveApplication(), function ($app): void {
            $app->useBootstrapPath(dirname(__DIR__).'/Fixtures/bootstrap');
        });
    }

    public function test_modules_listed_in_the_bootstrap_file_are_registered_and_wired(): void
    {
        $this->assertInstanceOf(BlogModule::class, Modules::find('blog'));

        $this->get('/blog')->assertOk();
    }
}
