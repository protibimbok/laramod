<?php

namespace Laramod\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Laramod\ModuleRegistry;
use Laramod\Tests\Fixtures\Modules\Blog\BlogModule;
use Laramod\Tests\TestCase;

class ListCommandTest extends TestCase
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

    public function test_it_lists_the_modules_as_json(): void
    {
        $this->withoutMockingConsoleOutput()->artisan('laramod:list', ['--json' => true]);

        $path = (new BlogModule)->path();

        $this->assertSame([[
            'name' => 'blog',
            'class' => BlogModule::class,
            'path' => $path,
            'order' => 0,
            'capabilities' => [
                'routes' => true,
                'api' => true,
                'global-middlewares' => true,
                'migrations' => true,
                'seeders' => true,
                'commands' => true,
                'views' => true,
                'translations' => true,
                'config' => true,
            ],
            'ai_workflow' => null,
            'publish_tags' => ['blog-views', 'blog-config', 'blog-lang', 'blog-migrations'],
        ]], json_decode(Artisan::output(), true));
    }

    public function test_it_lists_what_every_module_provides(): void
    {
        $this->withoutMockingConsoleOutput()->artisan('laramod:list');

        $output = Artisan::output();

        $this->assertStringContainsString('blog', $output);
        $this->assertStringContainsString(BlogModule::class, $output);
        $this->assertStringContainsString('[OK] seeders', $output);
        $this->assertStringContainsString('[NO] ai-workflow', $output);
        $this->assertStringContainsString('blog-views, blog-config, blog-lang, blog-migrations', $output);
    }
}
