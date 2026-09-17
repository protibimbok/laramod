<?php

namespace Laramod\Contracts;

interface ProvidesGlobalMiddlewares
{
    /**
     * Get the middleware the module appends to the global middleware stack.
     *
     * @return list<class-string>
     */
    public function globalMiddlewares(): array;
}
