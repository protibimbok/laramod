<?php

namespace Laramod\Contracts;

interface ProvidesViews
{
    /**
     * Get the directory that holds the module's views, namespaced by the module name.
     */
    public function views(): string;
}
