<?php

namespace Laramod\Tests\Feature;

use Illuminate\Foundation\Vite;
use Illuminate\Support\Facades\Blade;
use InvalidArgumentException;
use Laramod\Facades\Modules;
use Laramod\ModuleRegistry;
use Laramod\Tests\Fixtures\Modules\Blog\BlogModule;
use Laramod\Tests\TestCase;

class ModuleViteTest extends TestCase
{
    protected string $hotFile;

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

    protected function setUp(): void
    {
        parent::setUp();

        // The package root stands in for the application, so the fixture module lives inside the project.
        $this->app->setBasePath(dirname(__DIR__, 2));

        // A hot file makes Laravel point at the dev server, which needs no build to test against.
        file_put_contents($this->hotFile = sys_get_temp_dir().'/laramod-hot-'.uniqid(), 'http://localhost:5173');

        $this->app->make(Vite::class)->useHotFile($this->hotFile);
    }

    protected function tearDown(): void
    {
        unlink($this->hotFile);

        parent::tearDown();
    }

    public function test_a_modules_entry_is_loaded_by_the_path_vite_knows_it_by(): void
    {
        $tags = Modules::vite('blog', 'resources/js/app.js')->toHtml();

        $this->assertStringContainsString('src="http://localhost:5173/tests/Fixtures/Modules/Blog/resources/js/app.js"', $tags);
    }

    public function test_a_view_loads_an_entry_through_the_facade_alias(): void
    {
        $html = Blade::render("{{ Modules::vite('blog', 'resources/js/app.js') }}");

        $this->assertStringContainsString('<script type="module" src="http://localhost:5173/tests/Fixtures/Modules/Blog/resources/js/app.js"', $html);
    }

    public function test_the_alias_is_registered_through_package_discovery(): void
    {
        $composer = json_decode(file_get_contents(dirname(__DIR__, 2).'/composer.json'), true);

        $this->assertSame(['Modules' => Modules::class], $composer['extra']['laravel']['aliases']);
    }

    public function test_several_entries_are_loaded_at_once(): void
    {
        $tags = Modules::vite('blog', ['resources/js/app.js', 'resources/css/app.css'])->toHtml();

        $this->assertStringContainsString('/tests/Fixtures/Modules/Blog/resources/js/app.js"', $tags);
        $this->assertStringContainsString('href="http://localhost:5173/tests/Fixtures/Modules/Blog/resources/css/app.css"', $tags);
    }

    public function test_an_entry_the_module_does_not_declare_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Entry [tests/Fixtures/Modules/Blog/resources/js/admin.js] is not declared by ['.BlogModule::class.'::viteEntries()].');

        Modules::vite('blog', 'resources/js/admin.js');
    }

    public function test_an_unknown_module_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Module [shop] is not registered.');

        Modules::vite('shop', 'resources/js/app.js');
    }
}
