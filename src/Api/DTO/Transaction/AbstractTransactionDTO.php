<?php

namespace Iroge\LaravelTronModule\Api\DTO\Transaction;


use Brick\Math\BigDecimal;
use Illuminate\Support\Carbon;

abstract class AbstractTransactionDTO implements ITransactionDTO
{
    public function __construct(
        public array      $data,
        public string     $txid,
        public ?Carbon    $time,
        public bool       $success,
        public ?int       $blockNumber,
        public ?string    $ownerAddress,
        public ?string    $receiverAddress = null,
        public ?BigDecimal $amount = null,
    )
    {
    }
}
