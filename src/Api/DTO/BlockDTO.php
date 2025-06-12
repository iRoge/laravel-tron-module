<?php

namespace Iroge\LaravelTronModule\Api\DTO;

use Brick\Math\BigDecimal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Iroge\LaravelTronModule\Api\Api;
use Iroge\LaravelTronModule\Api\Helpers\AddressHelper;
use Iroge\LaravelTronModule\Api\Helpers\AmountHelper;

readonly class BlockDTO
{
    public function __construct(
        public array  $data,
        public string $blockID,
        public array  $transactions,
    )
    {
    }

    public function toArray(): array
    {
        return [
            'blockId' => $this->blockID,
            'data' => $this->data,
            'transactions' => $this->transactions,
        ];
    }

    public static function fromArray(array $data): ?static
    {
        $transactions = [];
        foreach ($data['transactions'] as $transactionArray) {
            $transactions[] = Api::getDtoByTransactionArray($transactionArray);
        }
        return new static(
            data: $data,
            blockID: $data['blockID'],
            transactions: $transactions,
        );
    }
}
