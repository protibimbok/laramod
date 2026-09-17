<?php

namespace Laramod\Console\Generators;

use Illuminate\Routing\Console\ControllerMakeCommand as BaseCommand;
use Laramod\Console\Concerns\TargetsModule;

class ControllerMakeCommand extends BaseCommand
{
    use TargetsModule;
}
