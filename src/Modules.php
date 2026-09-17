<?php

namespace Laramod;

use Illuminate\Support\Facades\Facade;
use Laramod\Contracts\Module;

/**
 * @method static \Laramod\ModuleRegistry register(iterable<class-string<Module>|Module>|string|Module $modules)
 * @method static array<string, Module> all()
 * @method static array<string, Module> providing(string $contract)
 * @method static Module|null find(string $name)
 * @method static bool has(string $name)
 * @method static array<string, bool> capabilities(Module $module)
 *
 * @see ModuleRegistry
 */
class Modules extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return ModuleRegistry::class;
    }
}
