<?php

namespace Laramod\Console\Generators;

use Illuminate\Foundation\Console\ObserverMakeCommand as BaseCommand;
use Laramod\Console\Concerns\TargetsModule;

class ObserverMakeCommand extends BaseCommand
{
    use TargetsModule;
}
