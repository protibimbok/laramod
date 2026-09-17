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

        return $module;
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
