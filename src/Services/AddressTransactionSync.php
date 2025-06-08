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

class AddressTransactionSync extends BaseSync
{
    protected readonly TronWallet $wallet;
    protected readonly TronNode $node;
    protected readonly Api $api;
    protected readonly ?WebhookHandlerInterface $webhookHandler;
    /** @var TronDeposit[] $webhooks */
    protected array $webhooks = [];
    protected readonly array $trc20Addresses;

    public function __construct(
        protected readonly TronAddress $address,
        protected readonly bool        $force = false
    )
    {
        $this->wallet = $this->address->wallet;
        $this->node = $this->wallet->node ?? Tron::getNode();
        $this->api = $this->node->api();

        $model = config('tron.webhook_handler');
        $this->webhookHandler = $model ? App::make($model) : null;

        $this->trc20Addresses = TronTRC20::pluck('address')->all();
    }

    public function run(): void
    {
        parent::run();

        if (!$this->address->available) {
            $this->log('No synchronization required, the address has not been available!');
            return;
        }

        $this->transactions()
            ->runWebhooks();
    }

    protected function transactions(): self
    {
        $minTimestamp = max(($this->address->sync_at?->getTimestamp() ?? 0) - 3600, 0) * 1000;

        $this->log('Method v1/accounts/' . $this->address->address . '/transactions started...');
        $transactions = $this->api
            ->getTransactions($this->address->address)
            ->limit(200)
            ->searchInterval(false)
            ->minTimestamp($minTimestamp);
        $this->log('Method v1/accounts/' . $this->address->address . '/transactions finished');

        $this->log('Method v1/accounts/' . $this->address->address . '/transactions/trc20 started...');
        $trc20Transfers = $this->api
            ->getTRC20Transfers($this->address->address)
            ->limit(200)
            ->minTimestamp($minTimestamp);
        $this->log('Method v1/accounts/' . $this->address->address . '/transactions/trc20 finished');

        $this->node->increment('requests', 2);

        foreach ($transactions as $item) {
            $this->handleTransaction($item);
        }

        foreach ($trc20Transfers as $item) {
            $this->handlerTRC20Transfer($item);
        }

        $this->address->update([
            'sync_at' => Date::now(),
            'touch_at' => $this->address->touch_at ?: Date::now(),
        ]);

        return $this;
    }

    protected function handleTransaction(AbstractTransactionDTO $transaction): void
    {
        $type = TransactionType::createByTransactionDtoClass(get_class($transaction));
        if (!$type) {
            return;
        }

        if (
            !$transaction instanceof DelegateV2ResourcesTransactionDTO
            && !$transaction instanceof UnDelegateV2ResourcesTransactionDTO
            && !$transaction instanceof UnFreezeBalanceV2TransactionDTO
            && !$transaction instanceof FreezeBalanceV2TransactionDTO
            && !$transaction instanceof TransferTransactionDTO
        ) {
            return;
        }

        DB::transaction(function () use ($type, $transaction) {
            $tronTransaction = TronTransaction::updateOrCreate([
                'txid' => $transaction->txid,
            ], [
                'type' => $type,
                'time_at' => $transaction->time,
                'from' => $transaction->ownerAddress,
                'to' => $transaction->receiverAddress,
                'amount' => $transaction->amount,
                'block_number' => $transaction->blockNumber,
                'debug_data' => $transaction->toArray(),
            ]);

            if ($transaction->receiverAddress === $this->address->address && $transaction instanceof TransferTransactionDTO) {
                $deposit = $this->address
                    ->deposits()
                    ->updateOrCreate([
                        'txid' => $transaction->txid,
                    ], [
                        'wallet_id' => $this->address->wallet_id,
                        'amount' => $transaction->amount,
                        'block_height' => $transaction->blockNumber ?? 0,
                        'confirmations' => $transaction->blockNumber && $transaction->blockNumber < $this->node->block_number ? $this->node->block_number - $transaction->blockNumber : 0,
                        'time_at' => $transaction->time ?? Date::now(),
                    ]);

                if ($deposit->wasRecentlyCreated) {
                    $deposit->setRelation('wallet', $this->wallet);
                    $deposit->setRelation('address', $this->address);

                    $this->webhooks[] = $deposit;
                }
            }

            if ($tronTransaction->wasRecentlyCreated) {
                if (
                    $transaction instanceof DelegateV2ResourcesTransactionDTO
                    || $transaction instanceof UnDelegateV2ResourcesTransactionDTO
                ) {
                    $tronDelegate = TronDelegate::query()
                        ->createOrFirst(
                            [
                                'owner_address' => $transaction->ownerAddress,
                                'receiver_address' => $transaction->receiverAddress,
                                'resource' => $transaction->resource,
                            ],
                            [
                                'amount' => 0
                            ]
                        );

                    $transactionAmount = (float)$transaction->amount->__toString();
                    $amount = $tronDelegate->amount + ($transaction instanceof DelegateV2ResourcesTransactionDTO ? $transactionAmount : -$transactionAmount);
                    $tronDelegate->amount = $amount;
                    $tronDelegate->save();
                }
            }
        });
    }

    protected function handlerTRC20Transfer(TRC20TransferDTO $transfer): void
    {
        if (!in_array($transfer->contractAddress, $this->trc20Addresses)) {
            return;
        }

        $type = new TransactionType(TransactionType::TRIGGER_SMART_CONTRACT);

        $transaction = TronTransaction::updateOrCreate([
            'txid' => $transfer->txid,
        ], [
            'type' => $type,
            'time_at' => $transfer->time,
            'from' => $transfer->from,
            'to' => $transfer->to,
            'amount' => $transfer->value,
            'trc20_contract_address' => $transfer->contractAddress,
            'debug_data' => $transfer->toArray(),
        ]);

        if ($transfer->to === $this->address->address) {
            $trc20 = TronTRC20::whereAddress($transfer->contractAddress)->first();
            if ($trc20) {
                $deposit = $this->address
                    ->deposits()
                    ->updateOrCreate([
                        'txid' => $transfer->txid,
                    ], [
                        'wallet_id' => $this->address->wallet_id,
                        'trc20_id' => $trc20->id,
                        'amount' => $transfer->value,
                        'time_at' => $transfer->time ?? Date::now(),
                    ]);

                if ($deposit->wasRecentlyCreated) {
                    $deposit->setRelation('wallet', $this->wallet);
                    $deposit->setRelation('address', $this->address);
                    $deposit->setRelation('trc20', $trc20);

                    $this->webhooks[] = $deposit;
                }

                if (!$deposit->block_height) {
                    try {
                        $this->log('We request information about block number of TRC-20 transaction ' . $transfer->txid . ' ...');
                        $blockNumber = $this->api->getTransferBlockNumber($transfer->txid);
                        $this->log('Information received successfully: ' . $blockNumber);

                        $deposit->update([
                            'block_height' => $blockNumber ?: null,
                            'confirmations' => $blockNumber && $blockNumber < $this->node->block_number ? $this->node->block_number - $blockNumber : 0,
                        ]);
                        $transaction->update([
                            'block_number' => $blockNumber ?: null,
                        ]);
                        $this->node->increment('requests', 1);
                    } catch (\Exception $e) {
                        $this->log('Error: ' . $e->getMessage());
                    }
                } else {
                    $deposit->update([
                        'confirmations' => $deposit->block_height < $this->node->block_number ? $this->node->block_number - $deposit->block_height : 0,
                    ]);
                }
            }
        }
    }

    protected function runWebhooks(): self
    {
        if ($this->webhookHandler) {
            foreach ($this->webhooks as $item) {
                $this->log('Call Webhook Handler for Deposit #' . $item->id . ': ' . print_r($item->toArray(), true));

                $this->webhookHandler->handle($item);
            }
        }

        return $this;
    }
}
