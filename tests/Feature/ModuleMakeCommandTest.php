<?php

namespace Laramod\Tests\Feature;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Laramod\Contracts\Module;
use Laramod\Contracts\Ordered;
use Laramod\ModuleRegistry;
use Laramod\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class ModuleMakeCommandTest extends TestCase
{
    protected string $basePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basePath = sys_get_temp_dir().'/laramod-make-'.uniqid();

        $files = new Filesystem;
        $files->makeDirectory($this->basePath.'/bootstrap', recursive: true);
        $files->copy(dirname(__DIR__, 2).'/stubs/modules.stub', $this->basePath.'/bootstrap/modules.php');

        $this->app->setBasePath($this->basePath);
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->basePath);

        parent::tearDown();
    }

    /**
     * @param  array<string, mixed>  $options
     * @param  list<string>  $files
     * @param  list<string>  $capabilities
     */
    #[DataProvider('variants')]
    public function test_it_creates_a_module(string $name, array $options, array $files, array $capabilities): void
    {
        $this->artisan('make:module', ['name' => $name, ...$options])->assertSuccessful();

        $this->assertSame($files, $this->files("Modules/{$name}"));

        $module = $this->module($name);

        $this->assertSame(strtolower($name), $module->name());
        $this->assertSame(realpath("{$this->basePath}/Modules/{$name}"), realpath($module->path()));
        $this->assertSame(
            $capabilities,
            array_keys(array_filter((new ModuleRegistry($this->app))->capabilities($module))),
        );
    }

    /**
     * @return array<string, array{string, array<string, mixed>, list<string>, list<string>}>
     */
    public static function variants(): array
    {
        return [
            'web and api' => ['Blog', [], [
                'BlogModule.php',
                'Database/Migrations/.gitkeep',
                'Http/Controllers/Api/BlogController.php',
                'Http/Controllers/BlogController.php',
                'Tests/Feature/BlogModuleTest.php',
                'ai-workflow/README.md',
                'config/blog.php',
                'lang/en/messages.php',
                'routes/api.php',
                'routes/web.php',
            ], ['routes', 'api', 'migrations', 'views', 'translations', 'config']],
            'api' => ['Billing', ['--api' => true], [
                'BillingModule.php',
                'Database/Migrations/.gitkeep',
                'Http/Controllers/Api/BillingController.php',
                'Tests/Feature/BillingModuleTest.php',
                'ai-workflow/README.md',
                'config/billing.php',
                'routes/api.php',
            ], ['api', 'migrations', 'config']],
            'plain' => ['Audit', ['--plain' => true], ['AuditModule.php'], []],
        ];
    }

    public function test_the_generated_files_are_filled_in(): void
    {
        $this->artisan('make:module', ['name' => 'user-profile'])->assertSuccessful();

        $path = $this->basePath.'/Modules/UserProfile';

        $this->assertStringContainsString(
            "\$router->get('/user-profile', [UserProfileController::class, 'index'])->name('user-profile.index');",
            file_get_contents($path.'/routes/web.php'),
        );
        $this->assertStringContainsString(
            "return response(__('user-profile::messages.title'));",
            file_get_contents($path.'/Http/Controllers/UserProfileController.php'),
        );
        $this->assertSame(['title' => 'User Profile'], require $path.'/lang/en/messages.php');
        $this->assertStringStartsWith("# User Profile Module\n", file_get_contents($path.'/ai-workflow/README.md'));
        $this->assertStringContainsString("['user-profile' => \$this->path().'/config/user-profile.php']", file_get_contents($path.'/UserProfileModule.php'));

        foreach ($this->files('Modules/UserProfile') as $file) {
            $this->assertStringNotContainsString('{{ ', file_get_contents($path.'/'.$file), $file);
        }
    }

    public function test_a_module_can_be_ordered(): void
    {
        $this->artisan('make:module', ['name' => 'Early', '--plain' => true, '--order' => '-5'])->assertSuccessful();

        $module = $this->module('Early');

        $this->assertInstanceOf(Ordered::class, $module);
        $this->assertSame(-5, $module->order());
    }

    public function test_modules_are_listed_in_the_bootstrap_file_in_the_order_they_are_made(): void
    {
        $this->artisan('make:module', ['name' => 'Shop', '--plain' => true])->assertSuccessful();
        $this->artisan('make:module', ['name' => 'Cart', '--plain' => true])->assertSuccessful();

        $this->assertSame(
            "<?php\n\nuse Modules\\Cart\\CartModule;\nuse Modules\\Shop\\ShopModule;\n\nreturn [\n    ShopModule::class,\n    CartModule::class,\n];\n",
            file_get_contents($this->basePath.'/bootstrap/modules.php'),
        );
    }

    public function test_a_class_that_shares_its_name_with_an_import_is_listed_by_its_full_name(): void
    {
        file_put_contents(
            $this->basePath.'/bootstrap/modules.php',
            "<?php\n\nuse Acme\\Forum\\ForumModule;\n\nreturn [\n    ForumModule::class,\n];\n",
        );

        $this->artisan('make:module', ['name' => 'Forum', '--plain' => true])->assertSuccessful();

        $this->assertSame(
            "<?php\n\nuse Acme\\Forum\\ForumModule;\n\nreturn [\n    ForumModule::class,\n    Modules\\Forum\\ForumModule::class,\n];\n",
            file_get_contents($this->basePath.'/bootstrap/modules.php'),
        );
    }

    public function test_it_tells_how_to_register_the_module_without_a_bootstrap_file(): void
    {
        unlink($this->basePath.'/bootstrap/modules.php');

        $this->withoutMockingConsoleOutput()->artisan('make:module', ['name' => 'Wiki', '--plain' => true]);

        $this->assertStringContainsString('Register [Modules\Wiki\WikiModule::class] yourself.', Artisan::output());
        $this->assertFileExists($this->basePath.'/Modules/Wiki/WikiModule.php');
        $this->assertFileDoesNotExist($this->basePath.'/bootstrap/modules.php');
    }

    public function test_it_follows_the_configured_path_and_namespace(): void
    {
        config(['laramod.path' => 'src/Domains', 'laramod.namespace' => 'Domains']);

        $this->artisan('make:module', ['name' => 'Ledger', '--plain' => true])->assertSuccessful();

        $this->assertStringContainsString(
            'namespace Domains\Ledger;',
            file_get_contents($this->basePath.'/src/Domains/Ledger/LedgerModule.php'),
        );
        $this->assertSame(['Domains\Ledger\LedgerModule'], require $this->basePath.'/bootstrap/modules.php');
    }

    public function test_published_stubs_are_preferred(): void
    {
        (new Filesystem)->ensureDirectoryExists($this->basePath.'/stubs/laramod/module');

        file_put_contents($this->basePath.'/stubs/laramod/module/module.plain.stub', '<?php // custom {{ class }}');

        $this->artisan('make:module', ['name' => 'Custom', '--plain' => true])->assertSuccessful();

        $this->assertSame('<?php // custom CustomModule', file_get_contents($this->basePath.'/Modules/Custom/CustomModule.php'));
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    #[DataProvider('invalidArguments')]
    public function test_it_rejects_invalid_arguments(array $arguments, string $message): void
    {
        (new Filesystem)->ensureDirectoryExists($this->basePath.'/Modules/Existing');

        $this->artisan('make:module', $arguments)
            ->expectsOutputToContain($message)
            ->assertFailed();

        $this->assertSame("<?php\n\nreturn [\n    //\n];\n", file_get_contents($this->basePath.'/bootstrap/modules.php'));
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function invalidArguments(): array
    {
        return [
            'existing module' => [['name' => 'existing'], 'Module [Modules/Existing] already exists.'],
            'invalid name' => [['name' => '9lives'], 'The name [9lives] is not a valid module name.'],
            'conflicting options' => [['name' => 'Blog', '--api' => true, '--plain' => true], 'cannot be combined'],
            'invalid order' => [['name' => 'Blog', '--order' => 'first'], 'The --order option must be an integer.'],
        ];
    }

    /**
     * Load the generated module class and create the module.
     */
    protected function module(string $name): Module
    {
        require_once "{$this->basePath}/Modules/{$name}/{$name}Module.php";

        return new ("Modules\\{$name}\\{$name}Module");
    }

    /**
     * Get the files beneath the given directory, relative to it.
     *
     * @return list<string>
     */
    protected function files(string $directory): array
    {
        $files = array_map(
            fn ($file): string => str_replace('\\', '/', $file->getRelativePathname()),
            (new Filesystem)->allFiles($this->basePath.'/'.$directory, hidden: true),
        );

        sort($files);

        return $files;
    }
}
