<?php

namespace Laramod\Console\Generators;

use Illuminate\Foundation\Console\ScopeMakeCommand as BaseCommand;
use Laramod\Console\Concerns\TargetsModule;

class ScopeMakeCommand extends BaseCommand
{
    use TargetsModule;

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        // Laravel looks for the directory in app/, a module always uses it.
        return $this->targetModule() ? $rootNamespace.'\\Models\\Scopes' : parent::getDefaultNamespace($rootNamespace);
    }
}
