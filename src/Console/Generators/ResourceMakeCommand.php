<?php

namespace Laramod\Console\Generators;

use Illuminate\Foundation\Console\ResourceMakeCommand as BaseCommand;
use Laramod\Console\Concerns\TargetsModule;

class ResourceMakeCommand extends BaseCommand
{
    use TargetsModule;
}
