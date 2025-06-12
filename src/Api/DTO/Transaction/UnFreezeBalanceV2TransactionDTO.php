<?php

namespace Iroge\LaravelTronModule\Api\DTO\Transaction;

use Brick\Math\BigDecimal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Iroge\LaravelTronModule\Api\Helpers\AddressHelper;
use Iroge\LaravelTronModule\Api\Helpers\AmountHelper;

class UnFreezeBalanceV2TransactionDTO extends AbstractTransactionDTO
{
    public function __construct(
        public array      $data,
        public string     $txid,
        public ?Carbon    $time,
        public bool       $success,
        public ?int       $blockNumber,
        public ?string     $ownerAddress,
        public string     $resource,
        public BigDecimal $unfreezeBalance,
    )
    {
        parent::__construct($data, $txid, $time, $success, $blockNumber, $ownerAddress, amount: $unfreezeBalance);
    }

    public function toArray(): array
    {
        return [
            'txid' => $this->txid,
            'time' => $this->time->toDateTimeString(),
            'success' => $this->success,
            'blockNumber' => $this->blockNumber,
            'owner_address' => $this->ownerAddress,
            'unfreeze_balance' => $this->unfreezeBalance->__toString(),
        ];
    }

    public static function fromArray(array $data): ?static
    {
        if (($data['raw_data']['contract'][0]['type'] ?? null) !== 'UnfreezeBalanceV2Contract') {
            return null;
        }

        $unfreezeBalance = $data['raw_data']['contract'][0]['parameter']['value']['unfreeze_balance'];
        $ownerAddress = $data['raw_data']['contract'][0]['parameter']['value']['owner_address'];
        $resource = $data['raw_data']['contract'][0]['parameter']['value']['resource'] ?? 'BANDWIDTH';

        if (isset($data['block_timestamp'])) {
            $date = Date::createFromTimestampMs($data['block_timestamp']);
        } else {
            $date = Date::createFromTimestampMs($data['timestamp']);
        }

        return new static(
            data: $data,
            txid: $data['txID'],
            time: $date,
            success: $data['ret'][0]['contractRet'] === 'SUCCESS',
            blockNumber: $data['blockNumber'] ?? null,
            ownerAddress: AddressHelper::toBase58($ownerAddress),
            resource: $resource,
            unfreezeBalance: AmountHelper::sunToDecimal($unfreezeBalance)
        );
    }
}
