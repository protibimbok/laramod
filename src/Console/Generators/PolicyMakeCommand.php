<?php

namespace Laramod\Console\Generators;

use Illuminate\Foundation\Console\PolicyMakeCommand as BaseCommand;
use Laramod\Console\Concerns\TargetsModule;

class PolicyMakeCommand extends BaseCommand
{
    use TargetsModule;
}
