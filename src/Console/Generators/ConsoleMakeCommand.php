<?php

namespace Laramod\Console\Generators;

use Illuminate\Foundation\Console\ConsoleMakeCommand as BaseCommand;
use Laramod\Console\Concerns\TargetsModule;

class ConsoleMakeCommand extends BaseCommand
{
    use TargetsModule;
}
