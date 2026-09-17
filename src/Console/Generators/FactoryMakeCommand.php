<?php

namespace Laramod\Console\Generators;

use Illuminate\Database\Console\Factories\FactoryMakeCommand as BaseCommand;
use Illuminate\Support\Str;
use Laramod\Console\Concerns\TargetsModule;

class FactoryMakeCommand extends BaseCommand
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

        if (! $module = $this->targetModule()) {
            return $class;
        }

        $model = class_basename($this->qualifyModel($this->option('model') ?: $this->guessModelName($name)));

        // Laravel finds neither the factory's namespace nor its model by convention inside a module, so both are explicit.
        $class = preg_replace(
            '/^namespace Database\\\\Factories/m',
            'namespace '.$this->moduleNamespace($module).'\\Database\\Factories',
            $class, 1,
        );

        return preg_replace('/^(class\s[^{]*\{\n)/m', <<<EOT
        $1    /**
             * The name of the factory's corresponding model.
             *
             * @var class-string<{$model}>
             */
            protected \$model = {$model}::class;


        EOT, $class, 1);
    }

    /**
     * Guess the model name from the Factory name or return a default model name.
     *
     * @param  string  $name
     */
    protected function guessModelName($name): string
    {
        if (! $this->targetModule()) {
            return parent::guessModelName($name);
        }

        // A module's model usually does not exist yet, so its conventional name beats Laravel's "Model" placeholder.
        return $this->qualifyModel(Str::after(Str::beforeLast($name, 'Factory'), $this->rootNamespace()));
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

        $name = Str::of($name)->replaceFirst($this->rootNamespace(), '')->finish('Factory')->replace('\\', '/');

        return $module->path().'/Database/Factories/'.$name.'.php';
    }
}
