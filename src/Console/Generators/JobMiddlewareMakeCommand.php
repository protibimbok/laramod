<?php

namespace Laramod\Console\Generators;

use Illuminate\Foundation\Console\JobMiddlewareMakeCommand as BaseCommand;
use Laramod\Console\Concerns\TargetsModule;

class JobMiddlewareMakeCommand extends BaseCommand
{
    use TargetsModule;
}
