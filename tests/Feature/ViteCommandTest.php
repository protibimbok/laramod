<?php

namespace Laramod\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Laramod\ModuleRegistry;
use Laramod\Tests\Fixtures\Modules\Blog\BlogModule;
use Laramod\Tests\Fixtures\Modules\Scratch\ScratchModule;
use Laramod\Tests\TestCase;

class ViteCommandTest extends TestCase
{
    /**
     * Register the fixture modules before the application boots.
     */
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        ScratchModule::$path = sys_get_temp_dir().'/laramod-scratch';

        $app->afterResolving(ModuleRegistry::class, function (ModuleRegistry $registry): void {
            $registry->register([BlogModule::class, ScratchModule::class]);
        });
    }

    public function test_it_outputs_every_module_with_the_entries_it_wants_built(): void
    {
        // The package root stands in for the application, so the fixture module lives inside the project.
        $this->app->setBasePath(dirname(__DIR__, 2));

        $this->withoutMockingConsoleOutput()->artisan('laramod:vite');

        $this->assertSame(['modules' => [
            [
                'name' => 'blog',
                'path' => 'tests/Fixtures/Modules/Blog',
                'file' => 'tests/Fixtures/Modules/Blog/BlogModule.php',
                'entries' => [
                    'tests/Fixtures/Modules/Blog/resources/js/app.js',
                    'tests/Fixtures/Modules/Blog/resources/css/app.css',
                ],
            ],
            [
                'name' => 'scratch',
                'path' => str_replace('\\', '/', ScratchModule::$path),
                'file' => 'tests/Fixtures/Modules/Scratch/ScratchModule.php',
                'entries' => [],
            ],
        ]], json_decode(Artisan::output(), true));
    }

    public function test_the_output_is_nothing_but_json(): void
    {
        $this->withoutMockingConsoleOutput()->artisan('laramod:vite');

        $this->assertJson($output = trim(Artisan::output()));
        $this->assertStringStartsWith('{"modules":[', $output);
    }
}
