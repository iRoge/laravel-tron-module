<?php

namespace Iroge\LaravelTronModule\Facades;

use Illuminate\Support\Facades\Facade;

class Tron extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Iroge\LaravelTronModule\Tron::class;
    }
}
