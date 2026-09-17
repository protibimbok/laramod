<?php

namespace Laramod\Contracts;

interface ProvidesViteEntries
{
    /**
     * Get the source files the module expects Vite to build, relative to the module: "resources/js/app.js".
     *
     * A view loads them with Modules::vite('blog', 'resources/js/app.js'), wherever the module is installed.
     *
     * @return list<string>
     */
    public function viteEntries(): array;
}
