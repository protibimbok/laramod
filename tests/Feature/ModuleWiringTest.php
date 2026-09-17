<?php

namespace Laramod\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Laramod\Facades\Modules;
use Laramod\ModuleRegistry;
use Laramod\Tests\Fixtures\Modules\Blog\BlogModule;
use Laramod\Tests\TestCase;

class ModuleWiringTest extends TestCase
{
    /**
     * Register the fixture module before the application boots.
     */
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app->afterResolving(ModuleRegistry::class, function (ModuleRegistry $registry): void {
            $registry->register(BlogModule::class);
        });
    }

    public function test_the_module_is_registered_through_the_facade(): void
    {
        $this->assertTrue(Modules::has('blog'));
        $this->assertInstanceOf(BlogModule::class, Modules::find('blog'));
    }

    public function test_web_routes_are_grouped_and_render_the_modules_views(): void
    {
        $this->assertContains('web', Route::getRoutes()->getByName('blog.index')->gatherMiddleware());

        $this->get('/blog')
            ->assertOk()
            ->assertSee('Fixture Blog')
            ->assertSee('Welcome to the blog')
            ->assertSee('<span class="badge">Module</span>', escape: false);
    }

    public function test_api_routes_are_prefixed_and_grouped(): void
    {
        $this->assertContains('api', Route::getRoutes()->getByName('blog.api.ping')->gatherMiddleware());

        $this->getJson('/api/blog/ping')
            ->assertOk()
            ->assertExactJson(['module' => 'blog']);
    }

    public function test_module_middleware_runs_globally(): void
    {
        $this->get('/not-a-module-route')
            ->assertNotFound()
            ->assertHeader('X-Blog-Module', 'enabled');
    }

    public function test_module_config_is_merged(): void
    {
        $this->assertSame(10, config('blog.per_page'));
    }

    public function test_module_migration_paths_are_registered(): void
    {
        $this->assertContains(
            (new BlogModule)->path().'/Database/Migrations',
            $this->app->make('migrator')->paths(),
        );
    }

    public function test_module_commands_are_registered(): void
    {
        $this->artisan('blog:ping')
            ->expectsOutput('pong')
            ->assertSuccessful();
    }
}
