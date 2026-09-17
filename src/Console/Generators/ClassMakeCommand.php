<?php

namespace Laramod\Console\Generators;

use Illuminate\Foundation\Console\ClassMakeCommand as BaseCommand;
use Laramod\Console\Concerns\TargetsModule;

class ClassMakeCommand extends BaseCommand
{
    use TargetsModule;
}
