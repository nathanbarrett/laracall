<?php

namespace Nathan Barrett\LaraCall\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Nathan Barrett\LaraCall\LaraCall
 */
class LaraCall extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Nathan Barrett\LaraCall\LaraCall::class;
    }
}
