<?php

namespace Laramod;

use Illuminate\Contracts\Foundation\CachesRoutes;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\View\Compilers\BladeCompiler;
use Laramod\Console\InitCommand;
use Laramod\Console\ListCommand;
use Laramod\Contracts\Module;
use Laramod\Contracts\ProvidesApiRoutes;
use Laramod\Contracts\ProvidesCommands;
use Laramod\Contracts\ProvidesConfig;
use Laramod\Contracts\ProvidesGlobalMiddlewares;
use Laramod\Contracts\ProvidesMigrations;
use Laramod\Contracts\ProvidesRoutes;
use Laramod\Contracts\ProvidesTranslations;
use Laramod\Contracts\ProvidesViews;

class LaramodServiceProvider extends ServiceProvider
{
    /**
     * Register the module registry and the application's modules.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/laramod.php', 'laramod');

        $this->app->singleton(ModuleRegistry::class);

        if (is_file($modules = $this->app->bootstrapPath('modules.php'))) {
            $this->app->make(ModuleRegistry::class)->register(require $modules);
        }

        $this->app->booting(function (): void {
            foreach ($this->modules(ProvidesConfig::class) as $module) {
                foreach ($module->config() as $key => $path) {
                    $this->mergeConfigFrom($path, $key);
                }
            }
        });
    }

    /**
     * Wire what every registered module provides into the application.
     */
    public function boot(): void
    {
        $this->bootGlobalMiddlewares();
        $this->bootRoutes();
        $this->bootViews();

        foreach ($this->modules(ProvidesTranslations::class) as $module) {
            $this->loadTranslationsFrom($module->translations(), $module->name());
            $this->loadJsonTranslationsFrom($module->translations());
        }

        foreach ($this->modules(ProvidesMigrations::class) as $module) {
            $this->loadMigrationsFrom($module->migrations());
        }

        if ($this->app->runningInConsole()) {
            foreach ($this->modules(ProvidesCommands::class) as $module) {
                $this->commands($module->commands());
            }

            $this->commands([InitCommand::class, ListCommand::class]);

            $this->publishes([
                __DIR__.'/../config/laramod.php' => $this->app->configPath('laramod.php'),
            ], 'laramod-config');
        }
    }

    /**
     * Append the modules' middleware to the global middleware stack.
     */
    protected function bootGlobalMiddlewares(): void
    {
        if (($modules = $this->modules(ProvidesGlobalMiddlewares::class)) === []) {
            return;
        }

        /** @var HttpKernel $kernel */
        $kernel = $this->app->make(Kernel::class);

        foreach ($modules as $module) {
            foreach ($module->globalMiddlewares() as $middleware) {
                $kernel->pushMiddleware($middleware);
            }
        }
    }

    /**
     * Register the modules' web and API routes within their configured groups.
     */
    protected function bootRoutes(): void
    {
        if ($this->app instanceof CachesRoutes && $this->app->routesAreCached()) {
            return;
        }

        $router = $this->app->make(Router::class);

        $groups = $this->app->make('config')->get('laramod.routes', []);

        foreach ($this->modules(ProvidesRoutes::class) as $module) {
            $router->group($groups['web'] ?? [], fn (Router $router) => $module->routes($router));
        }

        foreach ($this->modules(ProvidesApiRoutes::class) as $module) {
            $router->group($groups['api'] ?? [], fn (Router $router) => $module->apiRoutes($router));
        }
    }

    /**
     * Register the modules' views and Blade components beneath the module name.
     */
    protected function bootViews(): void
    {
        $modules = $this->modules(ProvidesViews::class);

        foreach ($modules as $module) {
            $this->loadViewsFrom($module->views(), $module->name());
        }

        $this->callAfterResolving('blade.compiler', function (BladeCompiler $blade) use ($modules): void {
            foreach ($modules as $module) {
                $blade->anonymousComponentPath($module->views().'/components', $module->name());
                $blade->componentNamespace(
                    Str::beforeLast($module::class, '\\').'\\View\\Components', $module->name()
                );
            }
        });
    }

    /**
     * Get the modules that implement the given capability contract.
     *
     * @template TContract of object
     *
     * @param  class-string<TContract>  $contract
     * @return array<string, Module&TContract>
     */
    protected function modules(string $contract): array
    {
        return $this->app->make(ModuleRegistry::class)->providing($contract);
    }
}
