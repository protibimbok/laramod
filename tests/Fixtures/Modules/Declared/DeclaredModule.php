<?php

namespace Laramod\Tests\Fixtures\Modules\Declared;

use Laramod\Contracts\Module;
use Laramod\Contracts\ProvidesMigrations;
use Laramod\Contracts\ProvidesViews;

class DeclaredModule implements Module, ProvidesMigrations, ProvidesViews
{
    /**
     * The directory the generator tests point the module at.
     */
    public static string $path;

    public function name(): string
    {
        return 'declared';
    }

    public function path(): string
    {
        return static::$path;
    }

    public function migrations(): array
    {
        return [$this->path().'/schema'];
    }

    public function views(): string
    {
        return $this->path().'/ui';
    }
}
