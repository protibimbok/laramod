<?php

namespace Laramod\Contracts;

use Illuminate\Console\Command;

interface ProvidesCommands
{
    /**
     * Get the module's Artisan commands.
     *
     * @return list<class-string<Command>>
     */
    public function commands(): array;
}
