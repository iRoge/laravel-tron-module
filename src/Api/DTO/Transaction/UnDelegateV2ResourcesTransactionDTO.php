<?php

namespace Iroge\LaravelTronModule\Api\DTO\Transaction;

use Brick\Math\BigDecimal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Iroge\LaravelTronModule\Api\Helpers\AddressHelper;
use Iroge\LaravelTronModule\Api\Helpers\AmountHelper;

class UnDelegateV2ResourcesTransactionDTO extends AbstractTransactionDTO
{
    public function __construct(
        public readonly array      $data,
        public readonly string     $txid,
        public readonly ?Carbon    $time,
        public readonly bool       $success,
        public readonly ?int       $blockNumber,
        public readonly string     $ownerAddress,
        public readonly string     $receiverAddress,
        public readonly string     $resource,
        public readonly BigDecimal $balance,
    )
    {
        parent::__construct($data, $this->txid, $time, $success, $this->blockNumber);
    }

    public function toArray(): array
    {
        return [
            'txid' => $this->txid,
            'time' => $this->time->toDateTimeString(),
            'success' => $this->success,
            'blockNumber' => $this->blockNumber,
            'ownerAddress' => $this->ownerAddress,
            'receiverAddress' => $this->receiverAddress,
            'resource' => $this->resource,
            'balance' => $this->balance->__toString(),
        ];
    }

    public static function fromArray(array $data): ?static
    {
        if (($data['raw_data']['contract'][0]['type'] ?? null) !== 'UnDelegateResourceContract') {
            return null;
        }
        $balance = $data['raw_data']['contract'][0]['parameter']['value']['balance'];
        $receiverAddress = $data['raw_data']['contract'][0]['parameter']['value']['receiver_address'];
        $ownerAddress = $data['raw_data']['contract'][0]['parameter']['value']['owner_address'];
        $resource = $data['raw_data']['contract'][0]['parameter']['value']['resource'];

        return new static(
            data: $data,
            txid: $data['txID'],
            time: isset($data['block_timestamp']) ? Date::createFromTimestampMs($data['block_timestamp']) : null,
            success: $data['ret'][0]['contractRet'] === 'SUCCESS',
            blockNumber: $data['blockNumber'] ?? null,
            ownerAddress: AddressHelper::toBase58($ownerAddress),
            receiverAddress: AddressHelper::toBase58($receiverAddress),
            resource: $resource,
            balance: AmountHelper::sunToDecimal($balance)
        );
    }
}
