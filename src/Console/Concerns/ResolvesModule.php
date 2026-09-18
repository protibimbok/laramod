<?php

namespace Laramod\Console\Concerns;

use Illuminate\Support\Str;
use Laramod\Contracts\Module;
use Laramod\ModuleRegistry;
use Symfony\Component\Console\Input\InputOption;
use Throwable;

trait ResolvesModule
{
    /**
     * Get the value of a command option. Provided by the console command the trait is used by.
     *
     * @param  string|null  $key
     * @return string|array|bool|null
     */
    abstract public function option($key = null);

    /**
     * Determine if the given option is present. Provided by the console command the trait is used by.
     *
     * @param  string  $name
     * @return bool
     */
    abstract public function hasOption($name);

    /**
     * Fail the command manually. Provided by the console command the trait is used by.
     *
     * @return never
     */
    abstract public function fail(Throwable|string|null $exception = null);

    /**
     * Add an option to the command. Provided by the console command the trait is used by.
     *
     * @param  string|array<int, string>|null  $shortcut
     * @return static
     */
    abstract public function addOption(string $name, string|array|null $shortcut = null, ?int $mode = null, string $description = '');

    /**
     * Get the module the command was asked to target, if any.
     */
    protected function targetModule(): ?Module
    {
        if (! $this->hasOption('module') || ($name = $this->option('module')) === null) {
            return null;
        }

        $modules = $this->laravel->make(ModuleRegistry::class);

        $module = $modules->find($name) ?? $modules->find(Str::kebab($name));

        if ($module === null) {
            $this->fail(sprintf('Module [%s] is not registered.', $name));
        }

        if (! $this->ownsModule($module)) {
            $this->fail(sprintf('Module [%s] is not part of the application and is read-only.', $module->name()));
        }

        return $module;
    }

    /**
     * Determine if the module is the application's own: inside the application and outside its vendor directory.
     *
     * Real paths are compared, because a package from a "path" repository is a symlink out of the vendor directory.
     */
    protected function ownsModule(Module $module): bool
    {
        $path = (realpath($module->path()) ?: $module->path()).DIRECTORY_SEPARATOR;
        $base = (realpath($this->laravel->basePath()) ?: $this->laravel->basePath()).DIRECTORY_SEPARATOR;

        return str_starts_with($path, $base) && ! str_starts_with($path, $base.'vendor'.DIRECTORY_SEPARATOR);
    }

    /**
     * Get the absolute path of what the module declares relative to itself.
     */
    protected function modulePath(Module $module, string $path): string
    {
        return $this->laravel->make(ModuleRegistry::class)->path($module, $path);
    }

    /**
     * Get the namespace the module's classes live in.
     */
    protected function moduleNamespace(Module $module): string
    {
        return Str::beforeLast($module::class, '\\');
    }

    /**
     * Add the "module" option to the command, whether it is defined by a signature or by getOptions().
     */
    protected function addModuleOption(): void
    {
        $this->addOption('module', null, InputOption::VALUE_REQUIRED, 'The module to create the file in');
    }
}
