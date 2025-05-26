<?php

namespace Iroge\LaravelTronModule\Api\DTO\Transaction;

interface ITransactionDTO
{
    public function toArray(): array;

    public static function fromArray(array $data): ?static;
}
