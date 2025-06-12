<?php

namespace Iroge\LaravelTronModule\Api\DTO\Transaction;

use Brick\Math\BigDecimal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Iroge\LaravelTronModule\Api\Helpers\AddressHelper;
use Iroge\LaravelTronModule\Api\Helpers\AmountHelper;

class FreezeBalanceV2TransactionDTO extends AbstractTransactionDTO
{
    public function __construct(
        public array      $data,
        public string     $txid,
        public ?Carbon    $time,
        public bool       $success,
        public ?int       $blockNumber,
        public ?string     $ownerAddress,
        public ?string     $resource,
        public BigDecimal $frozenBalance,
    )
    {
        parent::__construct($data, $txid, $time, $success, $blockNumber, $ownerAddress, amount: $frozenBalance);
    }

    public function toArray(): array
    {
        return [
            'txid' => $this->txid,
            'time' => $this->time->toDateTimeString(),
            'success' => $this->success,
            'blockNumber' => $this->blockNumber,
            'owner_address' => $this->ownerAddress,
            'resource' => $this->resource,
            'frozen_balance' => $this->frozenBalance->__toString(),
        ];
    }

    public static function fromArray(array $data): ?static
    {
        if (($data['raw_data']['contract'][0]['type'] ?? null) !== 'FreezeBalanceV2Contract') {
            return null;
        }

        $frozenBalance = $data['raw_data']['contract'][0]['parameter']['value']['frozen_balance'];
        $ownerAddress = $data['raw_data']['contract'][0]['parameter']['value']['owner_address'];
        $resource = $data['raw_data']['contract'][0]['parameter']['value']['resource'] ?? 'BANDWIDTH';

        return new static(
            data: $data,
            txid: $data['txID'],
            time: self::getDateFromArray($data),
            success: $data['ret'][0]['contractRet'] === 'SUCCESS',
            blockNumber: $data['blockNumber'] ?? null,
            ownerAddress: AddressHelper::toBase58($ownerAddress),
            resource: $resource,
            frozenBalance: AmountHelper::sunToDecimal($frozenBalance)
        );
    }
}
