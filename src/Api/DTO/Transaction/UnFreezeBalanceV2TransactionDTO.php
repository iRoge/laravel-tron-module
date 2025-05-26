<?php

namespace Iroge\LaravelTronModule\Api\DTO\Transaction;

use Brick\Math\BigDecimal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Iroge\LaravelTronModule\Api\Helpers\AddressHelper;
use Iroge\LaravelTronModule\Api\Helpers\AmountHelper;

class UnFreezeBalanceV2TransactionDTO implements ITransactionDTO
{
    public function __construct(
        public readonly array      $data,
        public readonly string     $txid,
        public readonly ?Carbon    $time,
        public readonly bool       $success,
        public readonly ?int       $blockNumber,
        public readonly string     $ownerAddress,
        public readonly string     $resource,
        public readonly BigDecimal $frozenBalance,
    )
    {
    }

    public function toArray(): array
    {
        return [
            'txid' => $this->txid,
            'time' => $this->time->toDateTimeString(),
            'success' => $this->success,
            'blockNumber' => $this->blockNumber,
            'owner_address' => $this->ownerAddress,
            'frozen_balance' => $this->frozenBalance->__toString(),
        ];
    }

    public static function fromArray(array $data): ?static
    {
        if (($data['raw_data']['contract'][0]['type'] ?? null) !== 'UnfreezeBalanceV2Contract') {
            return null;
        }

        $frozenBalance = $data['raw_data']['contract'][0]['parameter']['value']['frozen_balance'];
        $ownerAddress = $data['raw_data']['contract'][0]['parameter']['value']['owner_address'];
        $resource = $data['raw_data']['contract'][0]['parameter']['value']['resource'];

        return new static(
            data: $data,
            txid: $data['txID'],
            time: isset($data['block_timestamp']) ? Date::createFromTimestampMs($data['block_timestamp']) : null,
            success: $data['ret'][0]['contractRet'] === 'SUCCESS',
            blockNumber: $data['blockNumber'] ?? null,
            ownerAddress: AddressHelper::toBase58($ownerAddress),
            resource: $resource,
            frozenBalance: AmountHelper::sunToDecimal($frozenBalance)
        );
    }
}
