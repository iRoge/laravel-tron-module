<?php

namespace Iroge\LaravelTronModule\Api\DTO;

use Brick\Math\BigDecimal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Iroge\LaravelTronModule\Api\Helpers\AmountHelper;

class AccountDTO
{
    public function __construct(
        public readonly string   $address,
        public readonly array    $data,
        public readonly bool     $activated,
        public readonly ?BigDecimal $balance,
        public readonly ?BigDecimal $freeFrozenForEnergy,
        public readonly ?BigDecimal $freeFrozenForBandwidth,
        public readonly ?Carbon  $createTime,
        public readonly ?Carbon  $lastOperationTime,
    )
    {
    }

    public function toArray(): array
    {
        return [
            'address' => $this->address,
            'activated' => $this->activated,
            'balance' => $this->balance?->__toString(),
            'freeFrozenForEnergy' => $this->freeFrozenForEnergy?->__toString(),
            'freeFrozenForBandwidth' => $this->freeFrozenForBandwidth?->__toString(),
            'createTime' => $this->createTime?->toDateTimeString(),
            'lastOperationTime' => $this->lastOperationTime?->toDateTimeString(),
        ];
    }

    public static function fromArray(string $address, array $data): static
    {
        $activated = isset($data['create_time']);
        $balance = $activated ? AmountHelper::sunToDecimal($data['balance'] ?? 0) : null;
        $createTime = $activated ? Date::createFromTimestampMs($data['create_time']) : null;
        $lastOperationTime = $activated && isset($data['latest_opration_time']) ? Date::createFromTimestampMs($data['latest_opration_time']) : null;
        $freeFrozenForEnergy = AmountHelper::sunToDecimal($data['frozenV2'][1]['amount'] ?? 0);
        $freeFrozenForBandwidth = AmountHelper::sunToDecimal($data['frozenV2'][0]['amount'] ?? 0);

        return new static(
            address: $address,
            data: $data,
            activated: $activated,
            balance: $balance,
            freeFrozenForEnergy: $freeFrozenForEnergy,
            freeFrozenForBandwidth: $freeFrozenForBandwidth,
            createTime: $createTime,
            lastOperationTime: $lastOperationTime,
        );
    }
}
