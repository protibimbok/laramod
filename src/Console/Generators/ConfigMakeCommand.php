<?php

namespace Laramod\Console\Generators;

use Illuminate\Foundation\Console\ConfigMakeCommand as BaseCommand;
use Illuminate\Support\Str;
use Laramod\Console\Concerns\TargetsModule;

class ConfigMakeCommand extends BaseCommand
{
    use TargetsModule;

    /**
     * Get the destination file path.
     *
     * @param  string  $name
     */
    protected function getPath($name): string
    {
        return ($module = $this->targetModule())
            ? $module->path().'/config/'.Str::finish($this->argument('name'), '.php')
            : parent::getPath($name);
    }
}
