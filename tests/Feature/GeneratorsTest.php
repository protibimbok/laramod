<?php

namespace Laramod\Tests\Feature;

use Closure;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Laramod\ModuleRegistry;
use Laramod\Tests\Fixtures\Modules\Declared\DeclaredModule;
use Laramod\Tests\Fixtures\Modules\Scratch\ScratchModule;
use Laramod\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class GeneratorsTest extends TestCase
{
    protected const string SCRATCH = 'Laramod\Tests\Fixtures\Modules\Scratch';

    protected string $basePath;

    protected Closure $autoloader;

    /**
     * Register the fixture modules, pointed at a scratch directory, before the application boots.
     */
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $this->basePath = sys_get_temp_dir().'/laramod-generators-'.uniqid();

        ScratchModule::$path = $this->basePath.'/Modules/Scratch';
        DeclaredModule::$path = $this->basePath.'/Modules/Declared';

        $app->afterResolving(ModuleRegistry::class, function (ModuleRegistry $registry): void {
            $registry->register([ScratchModule::class, DeclaredModule::class]);
        });
    }

    protected function setUp(): void
    {
        parent::setUp();

        $files = new Filesystem;
        $files->makeDirectory($this->basePath.'/app', recursive: true);
        $files->makeDirectory(ScratchModule::$path, recursive: true);
        $files->put($this->basePath.'/composer.json', '{"autoload": {"psr-4": {"App\\\\": "app/"}}}');

        $this->app->setBasePath($this->basePath);

        // Generators ask to create the classes they cannot find, so the scratch module is autoloaded like a real one.
        spl_autoload_register($this->autoloader = function (string $class): void {
            $file = ScratchModule::$path.str_replace([self::SCRATCH, '\\'], ['', '/'], $class).'.php';

            if (str_starts_with($class, self::SCRATCH.'\\') && is_file($file)) {
                require $file;
            }
        });
    }

    protected function tearDown(): void
    {
        spl_autoload_unregister($this->autoloader);

        (new Filesystem)->deleteDirectory($this->basePath);

        parent::tearDown();
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @param  array<string, list<string>>  $expected
     */
    #[DataProvider('generators')]
    public function test_a_generator_creates_its_files_inside_the_module(string $command, array $arguments, array $expected): void
    {
        $this->artisan($command, [...$arguments, '--module' => 'Scratch'])->assertSuccessful();

        $this->assertSame([], (new Filesystem)->allFiles($this->basePath.'/app'));

        foreach ($expected as $file => $fragments) {
            $this->assertFileExists(ScratchModule::$path.'/'.$file);

            foreach ($fragments as $fragment) {
                $this->assertStringContainsString($fragment, file_get_contents(ScratchModule::$path.'/'.$file), $file);
            }
        }
    }

    /**
     * @return array<string, array{string, array<string, mixed>, array<string, list<string>>}>
     */
    public static function generators(): array
    {
        $namespace = fn (string $suffix): string => 'namespace '.self::SCRATCH.$suffix.';';

        return [
            'cast' => ['make:cast', ['name' => 'Json'], ['Casts/Json.php' => [$namespace('\Casts')]]],
            'channel' => ['make:channel', ['name' => 'OrderChannel'], ['Broadcasting/OrderChannel.php' => [$namespace('\Broadcasting')]]],
            'class' => ['make:class', ['name' => 'Support/Money'], ['Support/Money.php' => [$namespace('\Support')]]],
            'command' => ['make:command', ['name' => 'SendEmails'], ['Console/Commands/SendEmails.php' => [$namespace('\Console\Commands')]]],
            'component' => ['make:component', ['name' => 'Alert'], [
                'View/Components/Alert.php' => [$namespace('\View\Components'), "view('scratch::components.alert')"],
                'resources/views/components/alert.blade.php' => [],
            ]],
            'config' => ['make:config', ['name' => 'extra'], ['config/extra.php' => ['return [']]],
            'controller' => ['make:controller', ['name' => 'PostController'], [
                'Http/Controllers/PostController.php' => [$namespace('\Http\Controllers'), "class PostController\n"],
            ]],
            'enum' => ['make:enum', ['name' => 'Status'], ['Enums/Status.php' => [$namespace('\Enums')]]],
            'event' => ['make:event', ['name' => 'PostPublished'], ['Events/PostPublished.php' => [$namespace('\Events')]]],
            'exception' => ['make:exception', ['name' => 'PostException'], ['Exceptions/PostException.php' => [$namespace('\Exceptions')]]],
            'factory' => ['make:factory', ['name' => 'PostFactory'], ['Database/Factories/PostFactory.php' => [
                $namespace('\Database\Factories'),
                'use '.self::SCRATCH.'\Models\Post;',
                'protected $model = Post::class;',
            ]]],
            'interface' => ['make:interface', ['name' => 'Publishable'], ['Contracts/Publishable.php' => [$namespace('\Contracts')]]],
            'job' => ['make:job', ['name' => 'PublishPost', '--test' => true], [
                'Jobs/PublishPost.php' => [$namespace('\Jobs')],
                'Tests/Feature/Jobs/PublishPostTest.php' => [$namespace('\Tests\Feature\Jobs')],
            ]],
            'job middleware' => ['make:job-middleware', ['name' => 'RateLimited'], ['Jobs/Middleware/RateLimited.php' => [$namespace('\Jobs\Middleware')]]],
            'listener' => ['make:listener', ['name' => 'SendNotice', '--event' => 'PostPublished'], ['Listeners/SendNotice.php' => [
                $namespace('\Listeners'),
                'use '.self::SCRATCH.'\Events\PostPublished;',
            ]]],
            'mail' => ['make:mail', ['name' => 'PostMail', '--markdown' => 'mail.post'], [
                'Mail/PostMail.php' => [$namespace('\Mail'), "markdown: 'scratch::mail.post'"],
                'resources/views/mail/post.blade.php' => [],
            ]],
            'middleware' => ['make:middleware', ['name' => 'EnsureOwner'], ['Http/Middleware/EnsureOwner.php' => [$namespace('\Http\Middleware')]]],
            'model with everything' => ['make:model', ['name' => 'Post', '--all' => true], [
                'Models/Post.php' => [
                    $namespace('\Models'),
                    'use '.self::SCRATCH.'\Database\Factories\PostFactory;',
                    "#[UseFactory(PostFactory::class)]\nclass Post extends Model",
                    "/** @use HasFactory<PostFactory> */\n    use HasFactory;",
                ],
                'Database/Factories/PostFactory.php' => [$namespace('\Database\Factories'), 'protected $model = Post::class;'],
                'Database/Seeders/PostSeeder.php' => [$namespace('\Database\Seeders')],
                'Http/Controllers/PostController.php' => ['use '.self::SCRATCH.'\Models\Post;'],
                'Http/Requests/StorePostRequest.php' => [$namespace('\Http\Requests')],
                'Policies/PostPolicy.php' => [$namespace('\Policies'), 'use '.self::SCRATCH.'\Models\Post;'],
            ]],
            'plain model' => ['make:model', ['name' => 'Tag'], ['Models/Tag.php' => [$namespace('\Models'), "class Tag extends Model\n{\n    //\n}"]]],
            'notification' => ['make:notification', ['name' => 'PostNotice', '--markdown' => 'mail.notice'], [
                'Notifications/PostNotice.php' => [$namespace('\Notifications'), "markdown('scratch::mail.notice')"],
                'resources/views/mail/notice.blade.php' => [],
            ]],
            'observer' => ['make:observer', ['name' => 'PostObserver', '--model' => 'Post'], ['Observers/PostObserver.php' => [
                $namespace('\Observers'),
                'use '.self::SCRATCH.'\Models\Post;',
            ]]],
            'policy' => ['make:policy', ['name' => 'PostPolicy'], ['Policies/PostPolicy.php' => [$namespace('\Policies')]]],
            'provider' => ['make:provider', ['name' => 'ScratchServiceProvider'], ['Providers/ScratchServiceProvider.php' => [$namespace('\Providers')]]],
            'request' => ['make:request', ['name' => 'StorePostRequest'], ['Http/Requests/StorePostRequest.php' => [$namespace('\Http\Requests')]]],
            'resource' => ['make:resource', ['name' => 'PostResource'], ['Http/Resources/PostResource.php' => [$namespace('\Http\Resources')]]],
            'rule' => ['make:rule', ['name' => 'Slug'], ['Rules/Slug.php' => [$namespace('\Rules')]]],
            'scope' => ['make:scope', ['name' => 'PublishedScope'], ['Models/Scopes/PublishedScope.php' => [$namespace('\Models\Scopes')]]],
            'seeder' => ['make:seeder', ['name' => 'PostSeeder'], ['Database/Seeders/PostSeeder.php' => [$namespace('\Database\Seeders')]]],
            'feature test' => ['make:test', ['name' => 'PostTest'], ['Tests/Feature/PostTest.php' => [$namespace('\Tests\Feature')]]],
            'unit test' => ['make:test', ['name' => 'SlugTest', '--unit' => true], ['Tests/Unit/SlugTest.php' => [$namespace('\Tests\Unit')]]],
            'trait' => ['make:trait', ['name' => 'HasSlug'], ['Concerns/HasSlug.php' => [$namespace('\Concerns')]]],
            'view' => ['make:view', ['name' => 'posts.index', '--test' => true], [
                'resources/views/posts/index.blade.php' => [],
                'Tests/Feature/View/Posts/IndexTest.php' => [$namespace('\Tests\Feature\View\Posts'), "view('scratch::posts.index'"],
            ]],
        ];
    }

    public function test_every_make_command_of_the_framework_accepts_the_module_option(): void
    {
        $generators = array_filter(
            Artisan::all(),
            // The "make:*-table" commands publish the framework's own migrations, which no module owns.
            fn ($command, string $name): bool => str_starts_with($name, 'make:')
                && ! str_ends_with($name, '-table')
                && str_starts_with($command::class, 'Illuminate\\'),
            ARRAY_FILTER_USE_BOTH,
        );

        $this->assertSame([], array_keys($generators), 'These generators are not replaced yet.');
    }

    public function test_a_migration_is_created_inside_the_module(): void
    {
        $this->artisan('make:migration', ['name' => 'create_comments_table', '--module' => 'scratch'])->assertSuccessful();

        $this->assertCount(1, glob(ScratchModule::$path.'/Database/Migrations/*_create_comments_table.php'));
    }

    public function test_the_path_option_of_a_migration_wins(): void
    {
        $this->artisan('make:migration', ['name' => 'create_tags_table', '--module' => 'Scratch', '--path' => 'custom'])->assertSuccessful();

        $this->assertCount(1, glob($this->basePath.'/custom/*_create_tags_table.php'));
    }

    public function test_the_directories_a_module_declares_are_used(): void
    {
        $this->artisan('make:migration', ['name' => 'create_notes_table', '--module' => 'Declared'])->assertSuccessful();
        $this->artisan('make:view', ['name' => 'notes', '--module' => 'Declared'])->assertSuccessful();

        $this->assertCount(1, glob(DeclaredModule::$path.'/schema/*_create_notes_table.php'));
        $this->assertFileExists(DeclaredModule::$path.'/ui/notes.blade.php');
    }

    public function test_generators_are_unchanged_without_the_module_option(): void
    {
        $this->artisan('make:model', ['name' => 'Post', '--factory' => true])->assertSuccessful();

        $this->assertStringContainsString('namespace App;', $model = file_get_contents($this->basePath.'/app/Post.php'));
        $this->assertStringContainsString('@use HasFactory<\Database\Factories\PostFactory>', $model);
        $this->assertStringNotContainsString('UseFactory', $model);
        $this->assertStringNotContainsString('protected $model', file_get_contents($this->basePath.'/database/factories/PostFactory.php'));
        $this->assertSame([], (new Filesystem)->allFiles(ScratchModule::$path));
    }

    #[DataProvider('foreignModulePaths')]
    public function test_a_module_that_is_not_the_applications_own_is_read_only(string $path): void
    {
        (new Filesystem)->makeDirectory(DeclaredModule::$path = str_replace('{base}', $this->basePath, $path), recursive: true);

        $this->artisan('make:model', ['name' => 'Post', '--module' => 'declared'])
            ->expectsOutputToContain('Module [declared] is not part of the application and is read-only.')
            ->assertFailed();

        $this->assertSame([], (new Filesystem)->allFiles(DeclaredModule::$path));

        (new Filesystem)->deleteDirectory(DeclaredModule::$path);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function foreignModulePaths(): array
    {
        return [
            'installed in vendor' => ['{base}/vendor/acme/declared'],
            'symlinked from a path repository' => ['{base}-declared'],
        ];
    }

    public function test_an_unknown_module_is_rejected(): void
    {
        $this->artisan('make:model', ['name' => 'Post', '--module' => 'Shop'])
            ->expectsOutputToContain('Module [Shop] is not registered.')
            ->assertFailed();

        $this->assertSame([], (new Filesystem)->allFiles($this->basePath.'/app'));
    }
}
