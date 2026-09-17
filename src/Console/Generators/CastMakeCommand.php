<?php

namespace Laramod\Console\Generators;

use Illuminate\Foundation\Console\CastMakeCommand as BaseCommand;
use Laramod\Console\Concerns\TargetsModule;

class CastMakeCommand extends BaseCommand
{
    use TargetsModule;
}
