<?php

namespace Iroge\LaravelTronModule\Api\DTO;


interface IDTO
{
    public function toArray(): array;

    public static function fromArray(array $data): static;
}
