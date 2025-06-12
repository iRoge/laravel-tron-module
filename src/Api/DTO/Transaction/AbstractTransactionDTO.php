<?php

namespace Iroge\LaravelTronModule\Api\DTO\Transaction;


use Brick\Math\BigDecimal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Iroge\LaravelTronModule\Api\DTO\IDTO;
use Iroge\LaravelTronModule\Api\Helpers\AddressHelper;

abstract class AbstractTransactionDTO implements IDTO
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

    public function toArray(): array
    {
        return [
            'txid' => $this->txid,
            'dara' => $this->data,
            'time' => $this->time->toDateTimeString(),
            'success' => $this->success,
            'blockNumber' => $this->blockNumber,
            'owner_address' => $this->ownerAddress,
        ];
    }

    public static function fromArray(array $data): static
    {
        if (
            !isset($data['raw_data']['contract'][0]['parameter']['value']['owner_address'])
            || !isset($data['txID'])
            || !isset($data['ret'][0]['contractRet'])
        ) {
            throw new \Exception('Bad transaction array: ' . print_r($data, true));
        }

        return new static(
            data: $data,
            txid: $data['txID'],
            time: self::getDateFromArray($data),
            success: $data['ret'][0]['contractRet'] === 'SUCCESS',
            blockNumber: $data['blockNumber'] ?? null,
            ownerAddress: AddressHelper::toBase58($data['raw_data']['contract'][0]['parameter']['value']['owner_address']),
        );
    }

    protected static function getDateFromArray(array $data): ?Carbon
    {
        $date = null;
        if (isset($data['block_timestamp'])) {
            $date = Date::createFromTimestampMs($data['block_timestamp']);
        } elseif (isset($data['raw_data']['timestamp'])) {
            $date = Date::createFromTimestampMs($data['raw_data']['timestamp']);
        }

        return $date;
    }
}
