<?php

namespace Laramod\Contracts;

use Illuminate\Routing\Router;

interface ProvidesApiRoutes
{
    /**
     * Register the module's API routes. They are wrapped in the configured "api" group.
     */
    public function apiRoutes(Router $router): void;
}
