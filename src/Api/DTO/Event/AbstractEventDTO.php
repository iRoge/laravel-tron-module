<?php

namespace Iroge\LaravelTronModule\Api\DTO\Event;

use Iroge\LaravelTronModule\Api\DTO\IDTO;

readonly abstract class AbstractEventDTO implements IDTO
{
    public function __construct(
        public array  $data,
        public string $eventName,
        public string $blockNumber,
        public string  $contractAddress,
    )
    {
    }

    public function toArray(): array
    {
        return [
            'eventName' => $this->eventName,
            'blockNumber' => $this->blockNumber,
            'data' => $this->data,
            'contractAddress' => $this->contractAddress,
        ];
    }

    public static function fromArray(array $data): static
    {
        if (
            !isset($data['block_number'])
            || !isset($data['event_name'])
            || !isset($data['contract_address'])
        ) {
            throw new \Exception('Bad event array: ' . print_r($data, true));
        }

        return new static(
            data: $data,
            eventName: $data['event_name'],
            blockNumber: $data['block_number'],
            contractAddress: $data['contract_address'],
        );
    }
}
