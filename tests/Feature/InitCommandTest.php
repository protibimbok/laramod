<?php

namespace Laramod\Tests\Feature;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Composer;
use Illuminate\Support\Facades\Artisan;
use Laramod\Tests\TestCase;
use Mockery\MockInterface;

class InitCommandTest extends TestCase
{
    protected string $basePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basePath = sys_get_temp_dir().'/laramod-init-'.uniqid();

        $files = new Filesystem;
        $files->makeDirectory($this->basePath.'/bootstrap', recursive: true);
        $files->put($this->basePath.'/composer.json', <<<'JSON'
        {
            "name": "laravel/laravel",
            "require": {},
            "autoload": {
                "psr-4": {
                    "App\\": "app/"
                }
            }
        }

        JSON);
        $files->put($this->basePath.'/phpunit.xml', <<<'XML'
        <phpunit>
            <testsuites>
                <testsuite name="Unit">
                    <directory>tests/Unit</directory>
                </testsuite>
                <testsuite name="Feature">
                    <directory>tests/Feature</directory>
                </testsuite>
            </testsuites>
        </phpunit>

        XML);

        $this->app->setBasePath($this->basePath);
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->basePath);

        parent::tearDown();
    }

    public function test_it_prepares_the_application_for_modules(): void
    {
        $this->expectAutoloadDumps(1);

        $this->artisan('laramod:init')->assertSuccessful();

        $this->assertFileExists($this->basePath.'/Modules/.gitkeep');
        $this->assertSame([], require $this->basePath.'/bootstrap/modules.php');
        $this->assertFileEquals(dirname(__DIR__, 2).'/config/laramod.php', $this->basePath.'/config/laramod.php');

        $composer = json_decode(file_get_contents($this->basePath.'/composer.json'));

        $this->assertSame('Modules/', $composer->autoload->{'psr-4'}->{'Modules\\'});
        $this->assertSame('app/', $composer->autoload->{'psr-4'}->{'App\\'});
        $this->assertStringContainsString(
            "    \"require\": {},\n    \"autoload\": {\n        \"psr-4\": {\n            \"App\\\\\": \"app/\",\n            \"Modules\\\\\": \"Modules/\"\n        }\n    }\n",
            file_get_contents($this->basePath.'/composer.json'),
        );

        $this->assertStringContainsString(
            "            <directory>tests/Unit</directory>\n            <directory>Modules/*/Tests/Unit</directory>\n",
            $phpunit = file_get_contents($this->basePath.'/phpunit.xml'),
        );
        $this->assertStringContainsString(
            "            <directory>tests/Feature</directory>\n            <directory>Modules/*/Tests/Feature</directory>\n",
            $phpunit,
        );
    }

    public function test_it_changes_nothing_when_run_again(): void
    {
        $this->expectAutoloadDumps(1);

        $this->artisan('laramod:init')->assertSuccessful();

        $before = $this->snapshot();

        $this->artisan('laramod:init')->assertSuccessful();

        $this->assertSame($before, $this->snapshot());
    }

    public function test_it_follows_the_configured_path_and_namespace(): void
    {
        $this->expectAutoloadDumps(1);

        config(['laramod.path' => 'src/Domains', 'laramod.namespace' => 'Domains']);

        $this->artisan('laramod:init')->assertSuccessful();

        $composer = json_decode(file_get_contents($this->basePath.'/composer.json'));

        $this->assertDirectoryExists($this->basePath.'/src/Domains');
        $this->assertSame('src/Domains/', $composer->autoload->{'psr-4'}->{'Domains\\'});
        $this->assertStringContainsString(
            '<directory>src/Domains/*/Tests/Unit</directory>',
            file_get_contents($this->basePath.'/phpunit.xml'),
        );
    }

    public function test_it_prints_manual_instructions_for_files_it_cannot_edit(): void
    {
        $this->expectAutoloadDumps(0);

        file_put_contents($this->basePath.'/composer.json', 'not json');
        file_put_contents($this->basePath.'/phpunit.xml', $phpunit = '<phpunit><testsuites/></phpunit>');

        $this->withoutMockingConsoleOutput()->artisan('laramod:init');

        $output = Artisan::output();

        $this->assertStringContainsString('"autoload.psr-4" section of composer.json', $output);
        $this->assertStringContainsString('<directory>Modules/*/Tests/Unit</directory> to the "Unit" test suite', $output);
        $this->assertStringContainsString('<directory>Modules/*/Tests/Feature</directory> to the "Feature" test suite', $output);

        $this->assertSame('not json', file_get_contents($this->basePath.'/composer.json'));
        $this->assertSame($phpunit, file_get_contents($this->basePath.'/phpunit.xml'));
        $this->assertFileExists($this->basePath.'/bootstrap/modules.php');
    }

    /**
     * Expect Composer's autoloader to be regenerated the given number of times.
     */
    protected function expectAutoloadDumps(int $times): void
    {
        $this->mock(Composer::class, function (MockInterface $composer) use ($times): void {
            $composer->shouldReceive('setWorkingPath')->with($this->basePath)->andReturnSelf();
            $composer->shouldReceive('dumpAutoloads')->times($times);
        });
    }

    /**
     * Get the contents of every file the command touches.
     *
     * @return array<string, string>
     */
    protected function snapshot(): array
    {
        $files = [];

        foreach ((new Filesystem)->allFiles($this->basePath, hidden: true) as $file) {
            $files[$file->getRelativePathname()] = $file->getContents();
        }

        ksort($files);

        return $files;
    }
}
