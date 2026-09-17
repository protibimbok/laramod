<?php

namespace Laramod\Contracts;

interface ProvidesMigrations
{
    /**
     * Get the directories that hold the module's migrations.
     *
     * @return list<string>
     */
    public function migrations(): array;
}
