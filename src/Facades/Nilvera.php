<?php

namespace ProfsCode\Nilvera\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \ProfsCode\Nilvera\Nilvera
 */
class Nilvera extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'nilvera';
    }
}
