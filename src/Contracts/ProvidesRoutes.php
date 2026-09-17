<?php

namespace Laramod\Contracts;

use Illuminate\Routing\Router;

interface ProvidesRoutes
{
    /**
     * Register the module's web routes. They are wrapped in the configured "web" group.
     */
    public function routes(Router $router): void;
}
