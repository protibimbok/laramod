<?php

namespace Laramod\Console\Generators;

use Illuminate\Foundation\Console\JobMakeCommand as BaseCommand;
use Laramod\Console\Concerns\TargetsModule;

class JobMakeCommand extends BaseCommand
{
    use TargetsModule;
}
