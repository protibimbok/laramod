<?php

namespace Laramod\Contracts;

interface ProvidesViteEntries
{
    /**
     * Get the source files the module expects Vite to build, e.g. its "resources/js/app.js".
     *
     * @return list<string>
     */
    public function viteEntries(): array;
}
