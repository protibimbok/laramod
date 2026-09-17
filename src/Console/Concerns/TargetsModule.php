<?php

namespace Laramod\Console\Concerns;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Laramod\Contracts\ProvidesViews;
use Symfony\Component\Console\Command\Command;

trait TargetsModule
{
    use ResolvesModule;

    public function __construct(Filesystem $files)
    {
        parent::__construct($files);

        $this->addModuleOption();
    }

    /**
     * Get the root namespace for the class.
     */
    protected function rootNamespace(): string
    {
        return ($module = $this->targetModule())
            ? $this->moduleNamespace($module).'\\'
            : parent::rootNamespace();
    }

    /**
     * Get the destination class path.
     *
     * @param  string  $name
     */
    protected function getPath($name): string
    {
        if (! $module = $this->targetModule()) {
            return parent::getPath($name);
        }

        return $module->path().'/'.str_replace('\\', '/', Str::replaceFirst($this->rootNamespace(), '', $name)).'.php';
    }

    /**
     * Get the first view directory path from the application configuration.
     *
     * @param  string  $path
     */
    protected function viewPath($path = ''): string
    {
        if (! $module = $this->targetModule()) {
            return parent::viewPath($path);
        }

        $views = $module instanceof ProvidesViews ? $module->views() : $module->path().'/resources/views';

        return $views.($path ? DIRECTORY_SEPARATOR.$path : $path);
    }

    /**
     * Qualify the given model class base name.
     */
    protected function qualifyModel(string $model): string
    {
        if (! $this->targetModule()) {
            return parent::qualifyModel($model);
        }

        $model = str_replace('/', '\\', ltrim($model, '\\/'));

        return Str::startsWith($model, $this->rootNamespace()) ? $model : $this->rootNamespace().'Models\\'.$model;
    }

    /**
     * Call another console command, keeping generators inside the targeted module.
     *
     * @param  Command|string  $command
     * @param  array<string, mixed>  $arguments
     */
    public function call($command, array $arguments = []): int
    {
        if (is_string($command) && str_starts_with($command, 'make:') && $this->targetModule()) {
            $arguments['--module'] ??= $this->option('module');
        }

        return parent::call($command, $arguments);
    }

    /**
     * Create the matching test case if requested.
     *
     * @param  string  $path
     */
    protected function handleTestCreation($path): bool
    {
        if (! $module = $this->targetModule()) {
            return parent::handleTestCreation($path);
        }

        if (! $this->option('test') && ! $this->option('pest') && ! $this->option('phpunit')) {
            return false;
        }

        return $this->call('make:test', [
            'name' => Str::of($path)->after($module->path())->beforeLast('.php')->append('Test')->replace('\\', '/')->value(),
            '--pest' => $this->option('pest'),
            '--phpunit' => $this->option('phpunit'),
            '--force' => $this->hasOption('force') && $this->option('force'),
        ]) === 0;
    }

    /**
     * Point the view name a generated class refers to at the module's view namespace.
     */
    protected function namespaceView(string $class, string $view): string
    {
        return $view !== '' && ($module = $this->targetModule())
            ? str_replace("'{$view}'", "'{$module->name()}::{$view}'", $class)
            : $class;
    }
}
