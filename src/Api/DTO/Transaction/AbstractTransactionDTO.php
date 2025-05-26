<?php

namespace Iroge\LaravelTronModule\Api\DTO\Transaction;


use Illuminate\Support\Carbon;

abstract class AbstractTransactionDTO implements ITransactionDTO
{
    public function __construct(
        public readonly array      $data,
        public readonly string     $txid,
        public readonly ?Carbon    $time,
        public readonly bool       $success,
        public readonly ?int       $blockNumber,
    )
    {
    }
}
