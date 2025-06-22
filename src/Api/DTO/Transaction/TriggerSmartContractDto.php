<?php

namespace Iroge\LaravelTronModule\Api\DTO\Transaction;

use Brick\Math\BigDecimal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Iroge\LaravelTronModule\Api\Helpers\AddressHelper;
use Iroge\LaravelTronModule\Api\Helpers\AmountHelper;

class TriggerSmartContractDto extends AbstractTransactionDTO
{
    public function __construct(
        public array      $data,
        public string     $txid,
        public ?Carbon    $time,
        public bool       $success,
    )
    {
        parent::__construct($data, $txid, $time, $success);
    }

    public function toArray(): array
    {
        return [
            'txid' => $this->txid,
            'time' => $this->time->toDateTimeString(),
            'success' => $this->success,
        ];
    }

    public static function fromArray(array $data): static
    {
        if (($data['raw_data']['contract'][0]['type'] ?? null) !== 'TriggerSmartContract') {
            throw new \Exception('Bad TransferContract transaction array: ' . print_r($data, true));
        }

        return new static(
            data: $data,
            txid: $data['txID'],
            time: self::getDateFromArray($data),
            success: $data['ret'][0]['contractRet'] === 'SUCCESS',
        );
    }
}
