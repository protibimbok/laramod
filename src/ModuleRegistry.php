<?php

namespace Laramod;

use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use Laramod\Contracts\Module;
use Laramod\Contracts\Ordered;
use Laramod\Contracts\ProvidesApiRoutes;
use Laramod\Contracts\ProvidesCommands;
use Laramod\Contracts\ProvidesConfig;
use Laramod\Contracts\ProvidesGlobalMiddlewares;
use Laramod\Contracts\ProvidesMigrations;
use Laramod\Contracts\ProvidesRoutes;
use Laramod\Contracts\ProvidesSeeders;
use Laramod\Contracts\ProvidesTranslations;
use Laramod\Contracts\ProvidesViews;
use LogicException;

class ModuleRegistry
{
    /**
     * The contracts a module may implement to provide a capability.
     *
     * @var array<string, class-string>
     */
    public const array CAPABILITIES = [
        'routes' => ProvidesRoutes::class,
        'api' => ProvidesApiRoutes::class,
        'global-middlewares' => ProvidesGlobalMiddlewares::class,
        'migrations' => ProvidesMigrations::class,
        'seeders' => ProvidesSeeders::class,
        'commands' => ProvidesCommands::class,
        'views' => ProvidesViews::class,
        'translations' => ProvidesTranslations::class,
        'config' => ProvidesConfig::class,
    ];

    /**
     * The registered modules, keyed by name in registration order.
     *
     * @var array<string, Module>
     */
    protected array $modules = [];

    /**
     * The registered modules in the order they should be wired.
     *
     * @var array<string, Module>|null
     */
    protected ?array $sorted = null;

    public function __construct(protected Container $container) {}

    /**
     * Register the given modules.
     *
     * @param  iterable<class-string<Module>|Module>|class-string<Module>|Module  $modules
     */
    public function register(iterable|string|Module $modules): static
    {
        foreach (is_iterable($modules) ? $modules : [$modules] as $module) {
            $module = is_string($module) ? $this->container->make($module) : $module;

            if (! $module instanceof Module) {
                throw new InvalidArgumentException(sprintf(
                    'Module [%s] must implement [%s].', get_debug_type($module), Module::class
                ));
            }

            if (isset($this->modules[$module->name()])) {
                throw new LogicException(sprintf(
                    'Duplicate module [%s] registered by [%s] and [%s].',
                    $module->name(), $this->modules[$module->name()]::class, $module::class
                ));
            }

            $this->modules[$module->name()] = $module;
        }

        $this->sorted = null;

        return $this;
    }

    /**
     * Get every module in the order it should be wired.
     *
     * @return array<string, Module>
     */
    public function all(): array
    {
        return $this->sorted ??= $this->sort($this->modules);
    }

    /**
     * Get the modules that implement the given capability contract.
     *
     * @template TContract of object
     *
     * @param  class-string<TContract>  $contract
     * @return array<string, Module&TContract>
     */
    public function providing(string $contract): array
    {
        return array_filter($this->all(), fn (Module $module): bool => $module instanceof $contract);
    }

    /**
     * Find a module by its name.
     */
    public function find(string $name): ?Module
    {
        return $this->modules[$name] ?? null;
    }

    /**
     * Determine if a module with the given name is registered.
     */
    public function has(string $name): bool
    {
        return isset($this->modules[$name]);
    }

    /**
     * Get every known capability and whether the module provides it.
     *
     * @return array<string, bool>
     */
    public function capabilities(Module $module): array
    {
        return array_map(fn (string $contract): bool => $module instanceof $contract, self::CAPABILITIES);
    }

    /**
     * Sort the modules by order, keeping registration order for ties. A negative order sorts last.
     *
     * @param  array<string, Module>  $modules
     * @return array<string, Module>
     */
    protected function sort(array $modules): array
    {
        $position = array_flip(array_keys($modules));

        $key = function (Module $module) use ($position): array {
            $order = $module instanceof Ordered ? $module->order() : 0;

            return [$order < 0, max($order, 0), $position[$module->name()]];
        };

        uasort($modules, fn (Module $a, Module $b): int => $key($a) <=> $key($b));

        return $modules;
    }
}
