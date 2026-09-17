<?php

namespace Laramod\Tests\Feature;

use Illuminate\Database\Seeder as DatabaseSeeder;
use InvalidArgumentException;
use Laramod\Facades\Seeder;
use Laramod\ModuleRegistry;
use Laramod\Tests\Fixtures\Modules\Blog\BlogModule;
use Laramod\Tests\Fixtures\Modules\Blog\Database\Seeders\BlogSettingsSeeder;
use Laramod\Tests\TestCase;

class SeedingTest extends TestCase
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

    public function test_the_facade_runs_the_modules_seeders_unguarded(): void
    {
        Seeder::runSeeders();

        $this->assertSame([[BlogSettingsSeeder::class, true]], config('blog.seeded'));
    }

    public function test_the_facade_runs_the_seeders_of_one_module(): void
    {
        Seeder::runSeeders('blog');

        $this->assertSame([[BlogSettingsSeeder::class, true]], config('blog.seeded'));
    }

    public function test_an_unknown_module_cannot_be_seeded(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Seeder::runSeeders('shop');
    }

    public function test_the_seeders_can_be_called_from_the_applications_own_seeder(): void
    {
        $seeder = new class extends DatabaseSeeder
        {
            public function run(): void
            {
                $this->call(Seeder::seeders());
            }
        };

        $seeder->setContainer($this->app)->__invoke();

        $this->assertSame([BlogSettingsSeeder::class], array_column(config('blog.seeded'), 0));
    }
}
