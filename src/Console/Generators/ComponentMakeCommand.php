<?php

namespace Laramod\Console\Generators;

use Illuminate\Foundation\Console\ComponentMakeCommand as BaseCommand;
use Laramod\Console\Concerns\TargetsModule;

class ComponentMakeCommand extends BaseCommand
{
    use TargetsModule;

    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     */
    protected function buildClass($name): string
    {
        return $this->namespaceView(parent::buildClass($name), $this->getView());
    }
}
