<?php

namespace Laramod\Console\Generators;

use Illuminate\Foundation\Console\TraitMakeCommand as BaseCommand;
use Laramod\Console\Concerns\TargetsModule;

class TraitMakeCommand extends BaseCommand
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
        return $this->targetModule() ? $rootNamespace.'\\Concerns' : parent::getDefaultNamespace($rootNamespace);
    }
}
