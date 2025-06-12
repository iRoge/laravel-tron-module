<?php

namespace Iroge\LaravelTronModule\Api\DTO\Event;

use Brick\Math\BigDecimal;
use Iroge\LaravelTronModule\Api\Helpers\AddressHelper;

readonly class TransferTrc20EventDTO extends AbstractEventDTO
{
    public function __construct(
        public array  $data,
        public string $eventName,
        public string $blockNumber,
        public string  $contractAddress,
        public string  $ownerAddress,
        public string  $receiverAddress,
        public BigDecimal $amount
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
            'ownerAddress' => $this->ownerAddress,
            'receiverAddress' => $this->receiverAddress,
            'amount' => $this->amount->toFloat()
        ];
    }

    public static function fromArray(array $data): static
    {
        if (
            !isset($data['block_number'])
            || !isset($data['event_name'])
            || $data['event_name'] !== 'Transfer'
            || !isset($data['contract_address'])
            || !isset($data['result']['value'])
        ) {
            throw new \Exception('Bad transfer event array: ' . print_r($data, true));
        }

        $ownerAddress = AddressHelper::toBase58('41' . substr($data['result'][0], 2));
        $receiverAddress = AddressHelper::toBase58('41' . substr($data['result'][1], 2));
        $value = BigDecimal::of($data['result']['value']);

        return new static(
            data: $data,
            eventName: $data['event_name'],
            blockNumber: $data['block_number'],
            contractAddress: $data['contract_address'],
            ownerAddress: $ownerAddress,
            receiverAddress: $receiverAddress,
            amount: $value
        );
    }
}
