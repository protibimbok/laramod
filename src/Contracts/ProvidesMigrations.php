<?php

namespace Laramod\Contracts;

interface ProvidesMigrations
{
    /**
     * Get the directories that hold the module's migrations, relative to the module: "Database/Migrations".
     *
     * @return list<string>
     */
    public function migrations(): array;
}
