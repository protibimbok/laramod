<?php

namespace Laramod;

use Illuminate\Console\Command;
use Illuminate\Contracts\Foundation\CachesRoutes;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Database\Console as DatabaseConsole;
use Illuminate\Foundation\Console as FoundationConsole;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Routing\Console as RoutingConsole;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\View\Compilers\BladeCompiler;
use Laramod\Console\Generators;
use Laramod\Console\InitCommand;
use Laramod\Console\ListCommand;
use Laramod\Console\ModuleMakeCommand;
use Laramod\Console\ViteCommand;
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
     * Laravel's generators and the ones that replace them to accept the "module" option.
     *
     * @var array<class-string<Command>, class-string<Command>>
     */
    protected const array GENERATORS = [
        FoundationConsole\CastMakeCommand::class => Generators\CastMakeCommand::class,
        FoundationConsole\ChannelMakeCommand::class => Generators\ChannelMakeCommand::class,
        FoundationConsole\ClassMakeCommand::class => Generators\ClassMakeCommand::class,
        FoundationConsole\ComponentMakeCommand::class => Generators\ComponentMakeCommand::class,
        FoundationConsole\ConfigMakeCommand::class => Generators\ConfigMakeCommand::class,
        FoundationConsole\ConsoleMakeCommand::class => Generators\ConsoleMakeCommand::class,
        RoutingConsole\ControllerMakeCommand::class => Generators\ControllerMakeCommand::class,
        FoundationConsole\EnumMakeCommand::class => Generators\EnumMakeCommand::class,
        FoundationConsole\EventMakeCommand::class => Generators\EventMakeCommand::class,
        FoundationConsole\ExceptionMakeCommand::class => Generators\ExceptionMakeCommand::class,
        DatabaseConsole\Factories\FactoryMakeCommand::class => Generators\FactoryMakeCommand::class,
        FoundationConsole\InterfaceMakeCommand::class => Generators\InterfaceMakeCommand::class,
        FoundationConsole\JobMakeCommand::class => Generators\JobMakeCommand::class,
        FoundationConsole\JobMiddlewareMakeCommand::class => Generators\JobMiddlewareMakeCommand::class,
        FoundationConsole\ListenerMakeCommand::class => Generators\ListenerMakeCommand::class,
        FoundationConsole\MailMakeCommand::class => Generators\MailMakeCommand::class,
        RoutingConsole\MiddlewareMakeCommand::class => Generators\MiddlewareMakeCommand::class,
        DatabaseConsole\Migrations\MigrateMakeCommand::class => Generators\MigrateMakeCommand::class,
        FoundationConsole\ModelMakeCommand::class => Generators\ModelMakeCommand::class,
        FoundationConsole\NotificationMakeCommand::class => Generators\NotificationMakeCommand::class,
        FoundationConsole\ObserverMakeCommand::class => Generators\ObserverMakeCommand::class,
        FoundationConsole\PolicyMakeCommand::class => Generators\PolicyMakeCommand::class,
        FoundationConsole\ProviderMakeCommand::class => Generators\ProviderMakeCommand::class,
        FoundationConsole\RequestMakeCommand::class => Generators\RequestMakeCommand::class,
        FoundationConsole\ResourceMakeCommand::class => Generators\ResourceMakeCommand::class,
        FoundationConsole\RuleMakeCommand::class => Generators\RuleMakeCommand::class,
        FoundationConsole\ScopeMakeCommand::class => Generators\ScopeMakeCommand::class,
        DatabaseConsole\Seeds\SeederMakeCommand::class => Generators\SeederMakeCommand::class,
        FoundationConsole\TestMakeCommand::class => Generators\TestMakeCommand::class,
        FoundationConsole\TraitMakeCommand::class => Generators\TraitMakeCommand::class,
        FoundationConsole\ViewMakeCommand::class => Generators\ViewMakeCommand::class,
    ];

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
                    $this->mergeConfigFrom($this->path($module, $path), $key);
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
            $this->loadTranslationsFrom($translations = $this->path($module, $module->translations()), $module->name());
            $this->loadJsonTranslationsFrom($translations);
        }

        foreach ($this->modules(ProvidesMigrations::class) as $module) {
            $this->loadMigrationsFrom($this->paths($module, $module->migrations()));
        }

        if ($this->app->runningInConsole()) {
            foreach ($this->modules(ProvidesCommands::class) as $module) {
                $this->commands($module->commands());
            }

            $this->commands([InitCommand::class, ListCommand::class, ModuleMakeCommand::class, ViteCommand::class]);

            $this->bootGenerators();
            $this->bootPublishing();

            $this->publishes([
                __DIR__.'/../config/laramod.php' => $this->app->configPath('laramod.php'),
            ], 'laramod-config');

            $this->publishes([
                __DIR__.'/../stubs/module' => $this->app->basePath('stubs/laramod/module'),
            ], 'laramod-stubs');
        }
    }

    /**
     * Replace Laravel's "make:*" generators with the ones that accept the "module" option.
     */
    protected function bootGenerators(): void
    {
        foreach (self::GENERATORS as $laravel => $generator) {
            $this->app->extend($laravel, fn ($command, $app) => $generator === Generators\MigrateMakeCommand::class
                ? new $generator($app['migration.creator'], $app['composer'])
                : new $generator($app['files']));
        }
    }

    /**
     * Let "vendor:publish" copy what the modules provide into the application, one tag per module and kind.
     */
    protected function bootPublishing(): void
    {
        foreach ($this->modules(ProvidesViews::class) as $module) {
            $this->publishes([
                $this->path($module, $module->views()) => $this->app->resourcePath('views/vendor/'.$module->name()),
            ], $module->name().'-views');
        }

        foreach ($this->modules(ProvidesConfig::class) as $module) {
            $this->publishes(array_combine(
                $this->paths($module, $module->config()),
                // A dotted key is a nested config file: "modules.blog" is read from config/modules/blog.php.
                array_map(fn (string $key): string => $this->app->configPath(str_replace('.', '/', $key).'.php'), array_keys($module->config())),
            ), $module->name().'-config');
        }

        foreach ($this->modules(ProvidesTranslations::class) as $module) {
            $this->publishes([
                $this->path($module, $module->translations()) => $this->app->langPath('vendor/'.$module->name()),
            ], $module->name().'-lang');
        }

        // A published workflow is the application's own copy of the module's guidance, and "laramod:list" prefers it.
        foreach ($this->app->make(ModuleRegistry::class)->all() as $module) {
            if (is_dir($workflow = $module->path().'/ai-workflow')) {
                $this->publishes([$workflow => $this->app->basePath('.ai/modules/'.$module->name())], $module->name().'-ai');
            }
        }

        // The file names are kept, so a published migration is the same migration and never runs twice.
        foreach ($this->modules(ProvidesMigrations::class) as $module) {
            $this->publishes(
                array_fill_keys($this->paths($module, $module->migrations()), $this->app->databasePath('migrations')),
                $module->name().'-migrations',
            );
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
            $this->loadViewsFrom($this->path($module, $module->views()), $module->name());
        }

        $this->callAfterResolving('blade.compiler', function (BladeCompiler $blade) use ($modules): void {
            foreach ($modules as $module) {
                $blade->anonymousComponentPath($this->path($module, $module->views()).'/components', $module->name());
                $blade->componentNamespace(
                    Str::beforeLast($module::class, '\\').'\\View\\Components', $module->name()
                );
            }
        });
    }

    /**
     * Get the absolute path of what a module declares relative to itself.
     */
    protected function path(Module $module, string $path): string
    {
        return $this->app->make(ModuleRegistry::class)->path($module, $path);
    }

    /**
     * Get the absolute paths of what a module declares relative to itself, keeping the keys.
     *
     * @template TKey of array-key
     *
     * @param  array<TKey, string>  $paths
     * @return array<TKey, string>
     */
    protected function paths(Module $module, array $paths): array
    {
        return array_map(fn (string $path): string => $this->path($module, $path), $paths);
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
