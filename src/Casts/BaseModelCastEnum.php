<?php

namespace Iroge\LaravelTronModule\Casts;

use BenSampo\Enum\Enum;

abstract class BaseModelCastEnum extends Enum
{
    protected static array $descriptions = [];

    public function __construct($enumValue)
    {
        parent::__construct((int)$enumValue);
    }

    public static function getDescriptions(): array
    {
        return static::$descriptions;
    }

    public static function getDescription($value): string
    {
        if (isset(static::$descriptions[$value])) {
            return static::$descriptions[$value];
        }

        return parent::getDescription($value);
    }


    public static function getDescriptionsLabels(array $values)
    {
        $instances = self::getValues($values);
        $labels = [];
        foreach ($instances as $instance) {
            $labels = '"' . $instance->description . '"';
        }

        return implode(', ', $labels);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->value,
            'description' => $this->description
        ];
    }
}
