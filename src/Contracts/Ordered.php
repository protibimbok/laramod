<?php

namespace Laramod\Contracts;

interface Ordered
{
    /**
     * Get the position in which the module is wired. Lower comes first; a negative order comes last.
     */
    public function order(): int;
}
