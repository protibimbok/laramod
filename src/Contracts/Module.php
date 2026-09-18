<?php

namespace Laramod\Contracts;

interface Module
{
    /**
     * Get the unique, kebab-case name of the module, e.g. "blog".
     *
     * The name is used as the module's view, translation and publish-tag namespace.
     */
    public function name(): string;

    /**
     * Get the absolute path to the module's root directory. The paths the module provides are relative to it.
     */
    public function path(): string;
}
