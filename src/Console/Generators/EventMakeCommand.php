<?php

namespace Laramod\Console\Generators;

use Illuminate\Foundation\Console\EventMakeCommand as BaseCommand;
use Laramod\Console\Concerns\TargetsModule;

class EventMakeCommand extends BaseCommand
{
    use TargetsModule;
}
