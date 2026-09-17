<?php

namespace Laramod\Console\Generators;

use Illuminate\Foundation\Console\RequestMakeCommand as BaseCommand;
use Laramod\Console\Concerns\TargetsModule;

class RequestMakeCommand extends BaseCommand
{
    use TargetsModule;
}
