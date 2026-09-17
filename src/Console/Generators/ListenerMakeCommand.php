<?php

namespace Laramod\Console\Generators;

use Illuminate\Foundation\Console\ListenerMakeCommand as BaseCommand;
use Illuminate\Support\Str;
use Laramod\Console\Concerns\TargetsModule;

class ListenerMakeCommand extends BaseCommand
{
    use TargetsModule;

    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     */
    protected function buildClass($name): string
    {
        $event = $this->option('event');

        // Laravel qualifies a relative event with the application namespace, so the module's is applied first.
        if ($this->targetModule() && $event && ! Str::startsWith($event, [$this->rootNamespace(), 'Illuminate', '\\'])) {
            $this->input->setOption('event', '\\'.$this->rootNamespace().'Events\\'.str_replace('/', '\\', $event));
        }

        return parent::buildClass($name);
    }
}
