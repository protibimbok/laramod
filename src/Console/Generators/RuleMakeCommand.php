<?php

namespace Laramod\Console\Generators;

use Illuminate\Foundation\Console\RuleMakeCommand as BaseCommand;
use Laramod\Console\Concerns\TargetsModule;

class RuleMakeCommand extends BaseCommand
{
    use TargetsModule;
}
