<?php

namespace Laramod\Console\Generators;

use Illuminate\Foundation\Console\NotificationMakeCommand as BaseCommand;
use Laramod\Console\Concerns\TargetsModule;

class NotificationMakeCommand extends BaseCommand
{
    use TargetsModule;

    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     */
    protected function buildClass($name): string
    {
        return $this->namespaceView(parent::buildClass($name), (string) $this->option('markdown'));
    }
}
