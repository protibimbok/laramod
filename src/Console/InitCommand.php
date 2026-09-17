<?php

namespace Laramod\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Composer;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'laramod:init')]
class InitCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laramod:init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prepare the application for modules';

    /**
     * The steps that could not be applied and have to be done by hand.
     *
     * @var list<string>
     */
    protected array $manual = [];

    public function __construct(protected Filesystem $files)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(Composer $composer): int
    {
        $this->manual = [];

        $this->report($this->modulesPath().'/', $this->createModulesDirectory());
        $this->report('bootstrap/modules.php', $this->createBootstrapFile());
        $this->report('config/laramod.php', $this->publishConfig());
        $this->report('composer.json', $autoload = $this->addAutoloadNamespace());
        $this->report('phpunit.xml', $this->addTestSuiteDirectories());

        if ($vite = $this->viteConfig()) {
            $this->report($vite, $this->checkVitePlugin($vite));
        }

        if ($autoload === 'updated') {
            $composer->setWorkingPath($this->laravel->basePath())->dumpAutoloads();
        }

        if ($this->manual !== []) {
            $this->components->warn('Some steps could not be applied automatically:');
            $this->components->bulletList($this->manual);
        }

        return self::SUCCESS;
    }

    /**
     * Create the directory that holds the application's modules.
     */
    protected function createModulesDirectory(): string
    {
        if ($this->files->isDirectory($path = $this->laravel->basePath($this->modulesPath()))) {
            return 'exists';
        }

        $this->files->makeDirectory($path, recursive: true);
        $this->files->put($path.'/.gitkeep', '');

        return 'created';
    }

    /**
     * Create the file that lists the application's modules.
     */
    protected function createBootstrapFile(): string
    {
        if ($this->files->exists($path = $this->laravel->bootstrapPath('modules.php'))) {
            return 'exists';
        }

        $this->files->copy(__DIR__.'/../../stubs/modules.stub', $path);

        return 'created';
    }

    /**
     * Publish the package's configuration file.
     */
    protected function publishConfig(): string
    {
        if ($this->files->exists($path = $this->laravel->configPath('laramod.php'))) {
            return 'exists';
        }

        $this->files->ensureDirectoryExists(dirname($path));
        $this->files->copy(__DIR__.'/../../config/laramod.php', $path);

        return 'created';
    }

    /**
     * Autoload the modules directory beneath the modules namespace.
     */
    protected function addAutoloadNamespace(): string
    {
        $namespace = trim($this->laravel['config']['laramod.namespace'], '\\').'\\';
        $directory = $this->modulesPath().'/';

        $contents = $this->files->exists($path = $this->laravel->basePath('composer.json'))
            ? $this->files->get($path)
            : '';

        if (isset(json_decode($contents, true)['autoload']['psr-4'][$namespace])) {
            return 'exists';
        }

        // The entry is inserted as text, because re-encoding would reformat the whole file.
        $entry = json_encode($namespace).': '.json_encode($directory, JSON_UNESCAPED_SLASHES);

        $updated = preg_replace_callback(
            '#("autoload"\s*:\s*\{\s*"psr-4"\s*:\s*\{[^{}]*?\n([ \t]*)"[^\n{}]*?)(\s*\})#',
            fn (array $match): string => $match[1].",\n".$match[2].$entry.$match[3],
            $contents, 1,
        );

        if (! is_string($updated) || (json_decode($updated, true)['autoload']['psr-4'][$namespace] ?? null) !== $directory) {
            return $this->manual(sprintf(
                'Add %s to the "autoload.psr-4" section of composer.json and run "composer dump-autoload"', $entry,
            ));
        }

        $this->files->put($path, $updated);

        return 'updated';
    }

    /**
     * Add every module's tests to the application's "Unit" and "Feature" test suites.
     */
    protected function addTestSuiteDirectories(): string
    {
        $phpunit = $this->files->exists($path = $this->laravel->basePath('phpunit.xml'))
            ? $this->files->get($path)
            : '';

        $updated = $phpunit;
        $manual = false;

        foreach (['Unit', 'Feature'] as $suite) {
            $directory = sprintf('<directory>%s/*/Tests/%s</directory>', $this->modulesPath(), $suite);

            if (str_contains($updated, $directory)) {
                continue;
            }

            $updated = preg_replace(
                '#^([ \t]*)<directory>(?:\./)?tests/'.$suite.'</directory>[ \t]*$#m',
                "$0\n$1".$directory,
                $updated, 1, $count,
            );

            if ($count === 0) {
                $manual = true;

                $this->manual(sprintf('Add %s to the "%s" test suite of phpunit.xml', $directory, $suite));
            }
        }

        if ($updated !== $phpunit) {
            $this->files->put($path, $updated);
        }

        return match (true) {
            $manual => 'manual',
            $updated !== $phpunit => 'updated',
            default => 'exists',
        };
    }

    /**
     * Get the name of the application's Vite configuration file, if it has one.
     */
    protected function viteConfig(): ?string
    {
        foreach (['vite.config.js', 'vite.config.ts', 'vite.config.mjs', 'vite.config.mts'] as $file) {
            if ($this->files->exists($this->laravel->basePath($file))) {
                return $file;
            }
        }

        return null;
    }

    /**
     * Tell how to build the modules' assets. The file is the user's own JavaScript, so it is never edited.
     */
    protected function checkVitePlugin(string $file): string
    {
        if (str_contains($this->files->get($this->laravel->basePath($file)), 'laramod-vite-plugin')) {
            return 'exists';
        }

        return $this->manual(sprintf(
            'Install "laramod-vite-plugin" and, in %s, import laramod from it and call laramod({...}) in place of laravel({...}) with the same options',
            $file,
        ));
    }

    /**
     * Get the modules directory, relative to the application's base path.
     */
    protected function modulesPath(): string
    {
        return trim($this->laravel['config']['laramod.path'], '/');
    }

    /**
     * Remember a step that has to be done by hand.
     */
    protected function manual(string $instruction): string
    {
        $this->manual[] = $instruction;

        return 'manual';
    }

    /**
     * Report the outcome of a step.
     */
    protected function report(string $target, string $status): void
    {
        $color = match ($status) {
            'created', 'updated' => 'green',
            'manual' => 'red',
            default => 'yellow',
        };

        $this->components->twoColumnDetail($target, sprintf('<fg=%s;options=bold>%s</>', $color, strtoupper($status)));
    }
}
