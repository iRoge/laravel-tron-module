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
            $this->log('No synchronization required, the address has not been available!');
            return;
        }

        $this
            ->accountWithResources()
            ->trc20Balances();
    }

    protected function accountWithResources(): self
    {
        $this->log('Method walletsolidity/getaccount started...');
        $getAccount = $this->api->getAccount($this->address->address);
        $this->log('Method walletsolidity/getaccount finished: ' . print_r($getAccount->toArray(), true));

        $this->log('Method wallet/getaccountresource started...');
        $getAccountResources = $this->api->getAccountResources($this->address->address);
        $this->log('Method wallet/getaccountresource finished: ' . print_r($getAccountResources->toArray(), true));

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
            $this->log('Get TRC20 Balance from contract *' . $trc20Address . '* started...');
            $balance = Tron::getTRC20Balance($this->address, $trc20Address);
            $this->log('Get TRC20 Balance from contract *' . $trc20Address . '* finished: ' . $balance->__toString());

            $balances[$trc20Address] = $balance->__toString();
        }

        $this->address->update([
            'trc20' => $balances,
        ]);

        $this->node->increment('requests', count($balances));

        return $this;
    }
}
