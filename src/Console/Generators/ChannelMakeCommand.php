<?php

namespace Laramod\Console\Generators;

use Illuminate\Foundation\Console\ChannelMakeCommand as BaseCommand;
use Laramod\Console\Concerns\TargetsModule;

class ChannelMakeCommand extends BaseCommand
{
    use TargetsModule;
}
