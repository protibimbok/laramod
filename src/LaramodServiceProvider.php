<?php

namespace Laramod;

use Illuminate\Support\ServiceProvider;

class LaramodServiceProvider extends ServiceProvider
{
    /**
     * Register the package's services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/laramod.php', 'laramod');

        $this->app->singleton(ModuleRegistry::class);
    }

    /**
     * Bootstrap the package's services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/laramod.php' => $this->app->configPath('laramod.php'),
            ], 'laramod-config');
        }
    }
}
