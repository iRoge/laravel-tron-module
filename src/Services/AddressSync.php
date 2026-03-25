<?php

namespace Iroge\LaravelTronModule\Services;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Iroge\LaravelTronModule\Api\Api;
use Iroge\LaravelTronModule\Api\DTO\Transaction\AbstractTransactionDTO;
use Iroge\LaravelTronModule\Api\DTO\Transaction\DelegateV2ResourcesTransactionDTO;
use Iroge\LaravelTronModule\Api\DTO\Transaction\FreezeBalanceV2TransactionDTO;
use Iroge\LaravelTronModule\Api\DTO\Transaction\TransferTransactionDTO;
use Iroge\LaravelTronModule\Api\DTO\Transaction\UnDelegateV2ResourcesTransactionDTO;
use Iroge\LaravelTronModule\Api\DTO\Transaction\UnFreezeBalanceV2TransactionDTO;
use Iroge\LaravelTronModule\Api\DTO\TRC20TransferDTO;
use Iroge\LaravelTronModule\Casts\TransactionType;
use Iroge\LaravelTronModule\Facades\Tron;
use Iroge\LaravelTronModule\Handlers\WebhookHandlerInterface;
use Iroge\LaravelTronModule\Models\TronAddress;
use Iroge\LaravelTronModule\Models\TronDelegate;
use Iroge\LaravelTronModule\Models\TronDeposit;
use Iroge\LaravelTronModule\Models\TronNode;
use Iroge\LaravelTronModule\Models\TronTransaction;
use Iroge\LaravelTronModule\Models\TronTRC20;
use Iroge\LaravelTronModule\Models\TronWallet;

class AddressSync extends BaseSync
{
    protected readonly TronWallet $wallet;
    protected readonly TronNode $node;
    protected readonly Api $api;
    protected readonly array $trc20Addresses;
    public function __construct(
        protected readonly TronAddress $address,
        protected readonly bool        $force = false
    )
    {
        $this->wallet = $this->address->wallet;
        $this->node = $this->wallet->node ?? Tron::getNode();
        $this->api = $this->node->api();
        $this->trc20Addresses = TronTRC20::pluck('address')->all();

    }

    public function run(): void
    {
        parent::run();

        if (!$this->address->available) {
            $this->log('Обновить адрес не получилось. Он еще не доступен (available=0)');
            return;
        }

        try {
            $this->log('Обновление общей информации и балансов...');
            $this
                ->accountWithResources()
                ->trc20Balances();
        } catch (\Throwable $exception) {
            $this->log('Ошибка: ' . $exception->getMessage());
            throw $exception;
        }
    }

    protected function accountWithResources(): self
    {
        $getAccount = $this->api->getAccount($this->address->address);

        $getAccountResources = $this->api->getAccountResources($this->address->address);

        $this->address->update([
            'activated' => $getAccount->activated,
            'balance' => $getAccount->balance,
            'account' => $getAccount->toArray(),
            'account_resources' => $getAccountResources->toArray(),
            'touch_at' => $this->address->touch_at ?: Date::now(),
        ]);
        $this->node->increment('requests', 2);

        return $this;
    }

    protected function trc20Balances(): self
    {
        $balances = [];

        foreach ($this->trc20Addresses as $trc20Address) {
            $balance = Tron::getTRC20Balance($this->address, $trc20Address);

            $balances[$trc20Address] = $balance->__toString();
        }

        $this->address->update([
            'trc20' => $balances,
        ]);

        $this->node->increment('requests', count($balances));

        return $this;
    }
}
