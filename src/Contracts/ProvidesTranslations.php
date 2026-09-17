<?php

namespace Laramod\Contracts;

interface ProvidesTranslations
{
    /**
     * Get the directory that holds the module's language files, namespaced by the module name.
     */
    public function translations(): string;
}
