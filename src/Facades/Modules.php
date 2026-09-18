<?php

namespace Laramod\Facades;

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\HtmlString;
use Laramod\Contracts\Module;
use Laramod\ModuleRegistry;

/**
 * @method static ModuleRegistry register(iterable<class-string<Module>|Module>|string|Module $modules)
 * @method static array<string, Module> all()
 * @method static array<string, Module> providing(string $contract)
 * @method static Module|null find(string $name)
 * @method static bool has(string $name)
 * @method static array<string, bool> capabilities(Module $module)
 * @method static string path(Module $module, string $path)
 * @method static list<string> viteEntries(Module $module)
 * @method static HtmlString vite(string $module, string|array $entries)
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
