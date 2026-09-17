<?php

namespace Laramod\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'make:module')]
class ModuleMakeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:module
        {name : The name of the module}
        {--api : Create a module that only serves API routes}
        {--plain : Create a module that provides nothing yet}
        {--order= : The position in which the module is wired}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new module';

    public function __construct(protected Filesystem $files)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $studly = Str::studly($this->argument('name'));

        if (! preg_match('/^[A-Z][A-Za-z0-9]*$/', $studly)) {
            $this->fail(sprintf('The name [%s] is not a valid module name.', $this->argument('name')));
        }

        if ($this->option('api') && $this->option('plain')) {
            $this->fail('The --api and --plain options cannot be combined.');
        }

        if ($this->option('order') !== null && filter_var($this->option('order'), FILTER_VALIDATE_INT) === false) {
            $this->fail('The --order option must be an integer.');
        }

        $directory = trim($this->laravel['config']['laramod.path'], '/').'/'.$studly;

        if ($this->files->exists($path = $this->laravel->basePath($directory))) {
            $this->fail(sprintf('Module [%s] already exists.', $directory));
        }

        $namespace = trim($this->laravel['config']['laramod.namespace'], '\\').'\\'.$studly;

        $replacements = [...$this->orderReplacements(), ...[
            '{{ namespace }}' => $namespace,
            '{{ class }}' => $studly.'Module',
            '{{ studly }}' => $studly,
            '{{ name }}' => Str::kebab($studly),
            '{{ title }}' => Str::headline($studly),
        ]];

        foreach ($this->stubs($studly, Str::kebab($studly)) as $file => $stub) {
            $this->files->ensureDirectoryExists(dirname($path.'/'.$file));

            $this->files->put($path.'/'.$file, $stub === null ? '' : strtr($this->stub($stub), $replacements));
        }

        $this->components->info(sprintf('Module [%s] created successfully.', $directory));

        $this->register($namespace.'\\'.$studly.'Module');

        return self::SUCCESS;
    }

    /**
     * Get the files of the module and the stubs they are created from.
     *
     * @return array<string, string|null>
     */
    protected function stubs(string $studly, string $name): array
    {
        if ($this->option('plain')) {
            return [$studly.'Module.php' => 'module.plain.stub'];
        }

        $api = [
            'Http/Controllers/Api/'.$studly.'Controller.php' => 'controller.api.stub',
            'routes/api.php' => 'routes.api.stub',
            'config/'.$name.'.php' => 'config.stub',
            'Database/Migrations/.gitkeep' => null,
            'ai-workflow/README.md' => 'ai-workflow.stub',
        ];

        if ($this->option('api')) {
            return [
                $studly.'Module.php' => 'module.api.stub',
                ...$api,
                'Tests/Feature/'.$studly.'ModuleTest.php' => 'test.api.stub',
            ];
        }

        return [
            $studly.'Module.php' => 'module.stub',
            'Http/Controllers/'.$studly.'Controller.php' => 'controller.stub',
            'routes/web.php' => 'routes.web.stub',
            'lang/en/messages.php' => 'lang.stub',
            ...$api,
            'Tests/Feature/'.$studly.'ModuleTest.php' => 'test.stub',
        ];
    }

    /**
     * Import the class at the top of the given file contents, keeping the imports sorted.
     *
     * @param  list<string>  $imports
     */
    protected function import(string $class, array $imports, string $contents): string
    {
        $imports[] = $class;

        sort($imports, SORT_STRING | SORT_FLAG_CASE);

        $block = implode('', array_map(fn (string $import): string => "use {$import};\n", $imports));

        return preg_replace(
            '/^<\?php\s*/', "<?php\n\n{$block}\n", preg_replace('/^use [^;]+;[ \t]*\R/m', '', $contents), 1,
        );
    }

    /**
     * Get the contents of the given stub, preferring the application's published copy.
     */
    protected function stub(string $stub): string
    {
        return $this->files->get(
            $this->files->exists($published = $this->laravel->basePath('stubs/laramod/module/'.$stub))
                ? $published
                : __DIR__.'/../../stubs/module/'.$stub
        );
    }

    /**
     * Get the replacements that make the module ordered, or leave it unordered.
     *
     * @return array<string, string>
     */
    protected function orderReplacements(): array
    {
        if ($this->option('order') === null) {
            return ["{{ orderedImport }}\n" => '', '{{ orderedContract }}' => '', "{{ orderMethod }}\n" => ''];
        }

        return [
            "{{ orderedImport }}\n" => "use Laramod\\Contracts\\Ordered;\n",
            '{{ orderedContract }}' => ', Ordered',
            "{{ orderMethod }}\n" => sprintf(
                "    public function order(): int\n    {\n        return %d;\n    }\n\n", $this->option('order'),
            ),
        ];
    }

    /**
     * List the module in the application's modules bootstrap file.
     */
    protected function register(string $class): void
    {
        $contents = $this->files->exists($path = $this->laravel->bootstrapPath('modules.php'))
            ? $this->files->get($path)
            : '';

        preg_match_all('/^use ([^;]+);[ \t]*\R/m', $contents, $matches);

        // A class that shares its name with an imported one is listed by its full name instead.
        $imported = ! in_array(class_basename($class), array_map(class_basename(...), $matches[1]), true);

        // The class is inserted as text, because the order of the list and its comments belong to the user.
        $updated = preg_replace_callback(
            '#(?:^[ \t]*//[ \t]*\R)?^\];#m',
            fn (): string => sprintf("    %s::class,\n];", $imported ? class_basename($class) : $class),
            $contents, 1, $count,
        );

        if ($count === 0) {
            $this->components->warn(sprintf(
                'Could not list the module in bootstrap/modules.php. Register [%s::class] yourself.', $class,
            ));

            return;
        }

        $this->files->put($path, $imported ? $this->import($class, $matches[1], $updated) : $updated);

        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($path, true);
        }

        $this->components->info('Module listed in [bootstrap/modules.php].');
    }
}
