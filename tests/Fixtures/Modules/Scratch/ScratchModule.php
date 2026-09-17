<?php

namespace Laramod\Tests\Fixtures\Modules\Scratch;

use Laramod\Contracts\Module;

class ScratchModule implements Module
{
    /**
     * The directory the generator tests point the module at.
     */
    public static string $path;

    public function name(): string
    {
        return 'scratch';
    }

    public function path(): string
    {
        return static::$path;
    }
}
