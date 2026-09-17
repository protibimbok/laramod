<?php

namespace Laramod\Tests\Feature;

use Illuminate\Filesystem\Filesystem;
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
            'ai_workflow' => $path.'/ai-workflow/README.md',
            'publish_tags' => ['blog-views', 'blog-config', 'blog-lang', 'blog-migrations', 'blog-ai'],
        ]], json_decode(Artisan::output(), true));
    }

    public function test_the_published_ai_workflow_is_preferred(): void
    {
        $files = new Filesystem;
        $files->deleteDirectory($base = sys_get_temp_dir().'/laramod-list');
        $files->makeDirectory($base.'/.ai/modules/blog', recursive: true);
        $files->put($base.'/.ai/modules/blog/README.md', '# Our Blog');

        $this->app->setBasePath($base);

        $this->withoutMockingConsoleOutput()->artisan('laramod:list', ['--json' => true]);

        $this->assertSame($base.'/.ai/modules/blog/README.md', json_decode(Artisan::output(), true)[0]['ai_workflow']);

        $files->deleteDirectory($base);
    }

    public function test_it_lists_what_every_module_provides(): void
    {
        $this->withoutMockingConsoleOutput()->artisan('laramod:list');

        $output = Artisan::output();

        $this->assertStringContainsString('blog', $output);
        $this->assertStringContainsString(BlogModule::class, $output);
        $this->assertStringContainsString('[OK] seeders', $output);
        $this->assertStringContainsString('[OK] ai-workflow', $output);
        $this->assertStringContainsString('blog-views, blog-config, blog-lang, blog-migrations, blog-ai', $output);
    }
}
