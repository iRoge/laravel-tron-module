<?php

namespace Iroge\LaravelTronModule\Casts;

class ResourceType extends BaseModelCastEnum
{
    const string ENERGY = 'ENERGY';
    const string BANDWIDTH = 'BANDWIDTH';

    protected static array $descriptions = [
        self::ENERGY => 'ENERGY',
        self::BANDWIDTH => 'BANDWIDTH',
    ];
}