<?php

namespace Laramod\Facades;

use Illuminate\Support\Facades\Facade;
use Laramod\ModuleSeeder;

/**
 * @method static void runSeeders(string|null $module = null)
 * @method static list<class-string<\Illuminate\Database\Seeder>> seeders(string|null $module = null)
 *
 * @see ModuleSeeder
 */
class Seeder extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return ModuleSeeder::class;
    }
}
