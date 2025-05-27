<?php

namespace Iroge\LaravelTronModule\Api\DTO\Transaction;

use Brick\Math\BigDecimal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Iroge\LaravelTronModule\Api\Helpers\AddressHelper;
use Iroge\LaravelTronModule\Api\Helpers\AmountHelper;

class DelegateV2ResourcesTransactionDTO extends AbstractTransactionDTO
{
    public function __construct(
        public array      $data,
        public string     $txid,
        public ?Carbon    $time,
        public bool       $success,
        public ?int       $blockNumber,
        public ?string     $ownerAddress,
        public ?string     $receiverAddress,
        public string     $resource,
        public BigDecimal $balance,
    )
    {
        parent::__construct($data, $txid, $time, $success, $blockNumber, $ownerAddress, $receiverAddress, $balance);
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
        if (($data['raw_data']['contract'][0]['type'] ?? null) !== 'DelegateResourceContract') {
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
