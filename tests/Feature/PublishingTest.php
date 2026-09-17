<?php

namespace Laramod\Tests\Feature;

use Closure;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Laramod\LaramodServiceProvider;
use Laramod\ModuleRegistry;
use Laramod\Tests\Fixtures\Modules\Blog\BlogModule;
use Laramod\Tests\Fixtures\Modules\Scratch\ScratchModule;
use Laramod\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class PublishingTest extends TestCase
{
    /**
     * Register the fixture modules before the application boots.
     */
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        ScratchModule::$path = sys_get_temp_dir().'/laramod-scratch';

        $app->useDatabasePath(sys_get_temp_dir().'/laramod-published');

        $app->afterResolving(ModuleRegistry::class, function (ModuleRegistry $registry): void {
            $registry->register([BlogModule::class, ScratchModule::class]);
        });
    }

    /**
     * @param  array<string, Closure(): string>  $paths
     */
    #[DataProvider('tags')]
    public function test_what_a_module_provides_is_publishable_under_its_own_tag(string $tag, array $paths): void
    {
        $module = (new BlogModule)->path();

        $expected = [];

        foreach ($paths as $from => $to) {
            $expected[$module.$from] = $to();
        }

        $this->assertSame($expected, ServiceProvider::pathsToPublish(LaramodServiceProvider::class, $tag));
    }

    /**
     * @return array<string, array{string, array<string, Closure(): string>}>
     */
    public static function tags(): array
    {
        return [
            'views' => ['blog-views', ['/resources/views' => fn () => resource_path('views/vendor/blog')]],
            'config' => ['blog-config', ['/config/blog.php' => fn () => config_path('blog.php')]],
            'lang' => ['blog-lang', ['/lang' => fn () => lang_path('vendor/blog')]],
            'migrations' => ['blog-migrations', ['/Database/Migrations' => fn () => database_path('migrations')]],
        ];
    }

    public function test_a_module_has_no_tags_for_what_it_does_not_provide(): void
    {
        $this->assertSame([], array_values(array_filter(
            ServiceProvider::publishableGroups(),
            fn (string $tag): bool => str_starts_with($tag, 'scratch-'),
        )));
    }

    public function test_published_migrations_keep_their_name(): void
    {
        $this->artisan('vendor:publish', ['--tag' => 'blog-migrations'])->assertSuccessful();

        $this->assertFileExists(database_path('migrations/2026_01_01_000000_create_blog_posts_table.php'));

        (new Filesystem)->deleteDirectory(database_path());
    }
}
