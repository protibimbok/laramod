<?php

namespace Laramod\Contracts;

interface ProvidesConfig
{
    /**
     * Get the module's configuration files, keyed by the configuration key they are merged into.
     *
     * @return array<string, string>
     */
    public function config(): array;
}
