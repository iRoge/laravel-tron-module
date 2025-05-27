<?php

namespace Iroge\LaravelTronModule\Api\DTO;


class AccountResourcesDTO
{
    public function __construct(
        public readonly string $address,
        public readonly array  $data,
        public readonly bool   $activated,
        public readonly ?int $freeBandwidthTotal,
        public readonly ?int $freeBandwidthUsed,
        public readonly ?int $freeBandwidthAvailable,
        public readonly ?int $bandwidthTotal,
        public readonly ?int $bandwidthUsed,
        public readonly ?int $bandwidthAvailable,
        public readonly ?int $energyTotal,
        public readonly ?int $energyUsed,
        public readonly ?int $energyAvailable,
    )
    {
    }

    public function toArray(): array
    {
        return [
            'address' => $this->address,
            'activated' => $this->activated,
            'bandwidth' => [
                'free_total' => $this->freeBandwidthTotal,
                'free_used' => $this->freeBandwidthUsed,
                'free_available' => $this->freeBandwidthAvailable,
                'total' => $this->bandwidthTotal,
                'used' => $this->bandwidthUsed,
                'available' => $this->bandwidthAvailable,
            ],
            'energy' => [
                'total' => $this->energyTotal,
                'used' => $this->energyUsed,
                'available' => $this->energyAvailable,
            ]
        ];
    }

    public static function fromArray(string $address, array $data): static
    {
        $activated = count($data) > 0;

        return new static(
            address: $address,
            data: $data,
            activated: $activated,
            freeBandwidthTotal: !$activated ? null : $data['freeNetLimit'] ?? 0,
            freeBandwidthUsed: !$activated ? null : $data['freeNetUsed'] ?? 0,
            freeBandwidthAvailable: !$activated ? null : ($data['freeNetLimit'] ?? 0) - ($data['freeNetUsed'] ?? 0),
            bandwidthTotal: !$activated ? null : $data['NetLimit'] ?? 0,
            bandwidthUsed: !$activated ? null : $data['NetUsed'] ?? 0,
            bandwidthAvailable: !$activated ? null : ($data['NetLimit'] ?? 0) - ($data['NetUsed'] ?? 0),
            energyTotal: !$activated ? null : $data['EnergyLimit'] ?? 0,
            energyUsed: !$activated ? null : $data['EnergyUsed'] ?? 0,
            energyAvailable: !$activated ? null : ($data['EnergyLimit'] ?? 0) - ($data['EnergyUsed'] ?? 0),
        );
    }
}
