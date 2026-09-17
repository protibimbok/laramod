<?php

namespace Laramod\Contracts;

use Illuminate\Database\Seeder;

interface ProvidesSeeders
{
    /**
     * Get the seeders that seed the data the module needs to work.
     *
     * @return list<class-string<Seeder>>
     */
    public function seeders(): array;
}
