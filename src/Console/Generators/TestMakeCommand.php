<?php

namespace Laramod\Console\Generators;

use Illuminate\Foundation\Console\TestMakeCommand as BaseCommand;
use Illuminate\Support\Str;
use Laramod\Console\Concerns\TargetsModule;

class TestMakeCommand extends BaseCommand
{
    use TargetsModule;

    /**
     * Get the root namespace for the class.
     */
    protected function rootNamespace(): string
    {
        return ($module = $this->targetModule())
            ? $this->moduleNamespace($module).'\\Tests'
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

        return $module->path().'/Tests'.str_replace('\\', '/', Str::replaceFirst($this->rootNamespace(), '', $name)).'.php';
    }
}
