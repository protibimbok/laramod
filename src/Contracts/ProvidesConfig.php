<?php

namespace Laramod\Contracts;

interface ProvidesConfig
{
    /**
     * Get the module's configuration files, relative to the module, keyed by the configuration key they are merged into.
     *
     * A dotted key such as "modules.blog" keeps clear of the framework's own keys and publishes to config/modules/blog.php.
     *
     * @return array<string, string>
     */
    public function config(): array;
}
