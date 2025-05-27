<?php

namespace Iroge\LaravelTronModule\Casts;

use Iroge\LaravelTronModule\Api\DTO\Transaction\DelegateV2ResourcesTransactionDTO;
use Iroge\LaravelTronModule\Api\DTO\Transaction\FreezeBalanceV2TransactionDTO;
use Iroge\LaravelTronModule\Api\DTO\Transaction\TransferTransactionDTO;
use Iroge\LaravelTronModule\Api\DTO\Transaction\UnDelegateV2ResourcesTransactionDTO;
use Iroge\LaravelTronModule\Api\DTO\Transaction\UnFreezeBalanceV2TransactionDTO;

class TransactionType extends BaseModelCastEnum
{
    const int TRANSFER = 1;
    const int FREEZE_V2 = 2;
    const int UNFREEZE_V2 = 3;
    const int DELEGATE_V2 = 4;
    const int UNDELEGATE_V2 = 5;
    const int TRIGGER_SMART_CONTRACT = 6;

    protected static array $descriptions = [
        self::TRANSFER => 'Transfer TRX',
        self::FREEZE_V2 => 'Freeze',
        self::UNFREEZE_V2 => 'Unfreeze',
        self::DELEGATE_V2 => 'Delegate',
        self::UNDELEGATE_V2 => 'Undelegate',
        self::TRIGGER_SMART_CONTRACT => 'Trigger smart contract',
    ];

    public static array $dtoMap = [
        TransferTransactionDTO::class => self::TRANSFER,
        DelegateV2ResourcesTransactionDTO::class => self::DELEGATE_V2,
        UnDelegateV2ResourcesTransactionDTO::class => self::UNDELEGATE_V2,
        FreezeBalanceV2TransactionDTO::class => self::FREEZE_V2,
        UnFreezeBalanceV2TransactionDTO::class => self::UNFREEZE_V2,
    ];

    public static function createByTransactionDtoClass(string $class): ?TransactionType
    {
        if (!isset(self::$dtoMap[$class])) {
            return null;
        }

        return new self(self::$dtoMap[$class]);
    }
}