<?php

namespace Laramod\Console\Generators;

use Illuminate\Foundation\Console\ProviderMakeCommand as BaseCommand;
use Laramod\Console\Concerns\TargetsModule;

class ProviderMakeCommand extends BaseCommand
{
    use TargetsModule;
}
