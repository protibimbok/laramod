<?php

namespace Laramod\Contracts;

use Illuminate\Database\Seeder;

interface ProvidesSeeders
{
    /**
     * Get the seeders that seed the module's data.
     *
     * @return list<class-string<Seeder>>
     */
    public function seeders(): array;
}
