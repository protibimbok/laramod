<?php

namespace Laramod\Console\Generators;

use Illuminate\Foundation\Console\ModelMakeCommand as BaseCommand;
use Illuminate\Support\Str;
use Laramod\Console\Concerns\TargetsModule;

class ModelMakeCommand extends BaseCommand
{
    use TargetsModule;

    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     */
    protected function buildClass($name): string
    {
        $class = parent::buildClass($name);

        if (! $this->targetModule() || ! ($this->option('factory') || $this->option('all'))) {
            return $class;
        }

        // Laravel cannot find a module's factory by convention, so the model names it.
        return preg_replace('/^class /m', '#[UseFactory('.class_basename($this->factory()).'::class)]'."\nclass ", $class, 1);
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        // Laravel looks for the directory in app/, a module always uses it.
        return $this->targetModule() ? $rootNamespace.'\\Models' : parent::getDefaultNamespace($rootNamespace);
    }

    /**
     * Build the replacements for a factory.
     *
     * @return array<string, string>
     */
    protected function buildFactoryReplacements(): array
    {
        if (! $this->targetModule() || ! ($this->option('factory') || $this->option('all'))) {
            return parent::buildFactoryReplacements();
        }

        $factory = class_basename($this->factory());

        return [
            '{{ factory }}' => "/** @use HasFactory<{$factory}> */\n    use HasFactory;",
            '{{ factoryImport }}' => implode("\n", [
                'use Illuminate\Database\Eloquent\Attributes\UseFactory;',
                'use Illuminate\Database\Eloquent\Factories\HasFactory;',
                'use '.$this->factory().';',
            ]),
        ];
    }

    /**
     * Get the class name of the factory "make:factory" creates for the model.
     */
    protected function factory(): string
    {
        $model = Str::of($this->argument('name'))->studly()->replace('/', '\\');

        return $this->moduleNamespace($this->targetModule()).'\\Database\\Factories\\'.$model.'Factory';
    }
}
