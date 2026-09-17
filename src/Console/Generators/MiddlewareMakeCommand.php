<?php

namespace Laramod\Console\Generators;

use Illuminate\Routing\Console\MiddlewareMakeCommand as BaseCommand;
use Laramod\Console\Concerns\TargetsModule;

class MiddlewareMakeCommand extends BaseCommand
{
    use TargetsModule;
}
