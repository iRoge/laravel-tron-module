<?php

namespace Iroge\LaravelTronModule\Api;

use Brick\Math\BigDecimal;
use Iroge\LaravelTronModule\Api\Exceptions\BadResponseException;
use Iroge\LaravelTronModule\Api\Helpers\AmountHelper;
use Iroge\LaravelTronModule\Models\TronAddress;
use kornrunner\Secp256k1;
use kornrunner\Signature\Signature;
use Iroge\LaravelTronModule\Api\DTO\AccountResourcesDTO;
use Iroge\LaravelTronModule\Api\DTO\TransactionInfoDTO;
use Iroge\LaravelTronModule\Api\DTO\TransferDTO;
use Iroge\LaravelTronModule\Api\Helpers\AddressHelper;
use Iroge\LaravelTronModule\Api\DTO\AccountDTO;
use Iroge\LaravelTronModule\Api\Methods\Transfer;
use Iroge\LaravelTronModule\Api\Methods\Transactions;
use Iroge\LaravelTronModule\Api\Methods\TRC20Transfer;
use Iroge\LaravelTronModule\Api\Methods\TRC20Transfers;

class Api
{
    public readonly ApiManager $manager;
    protected readonly array $chainParameters;

    public function __construct(
        ?HttpProvider $fullNode = null,
        ?HttpProvider $solidityNode = null,
        ?HttpProvider $eventServer = null,
        ?HttpProvider $signServer = null,
        ?HttpProvider $explorer = null,
    ) {
        $this->manager = new ApiManager(
            compact(
                'fullNode',
                'solidityNode',
                'eventServer',
                'signServer',
                'explorer'
            )
        );

        $chainParametersFromFile = file_get_contents(__DIR__.'/Resources/chain_parameters.json');
        $chainParametersFromFile = json_decode($chainParametersFromFile, true);
        $chainParameters = [];
        foreach ($chainParametersFromFile['chainParameter'] ?? [] as $item) {
            if (isset($item['key'], $item['value'])) {
                $chainParameters[$item['key']] = $item['value'];
            }
        }
        $this->chainParameters = $chainParameters;
    }

    public function chainParameter(string $name, mixed $default = null): mixed
    {
        return $this->chainParameters[$name] ?? $default;
    }

    public function isConnected(): array
    {
        return $this->manager->isConnected();
    }

    public function getAccount(string $address): AccountDTO
    {
        $address = AddressHelper::toBase58($address);

        $data = $this->manager->request('walletsolidity/getaccount', null, [
            'address' => AddressHelper::toHex($address),
        ]);

        return AccountDTO::fromArray($address, $data);
    }

    public function getAccountResources(string $address): AccountResourcesDTO
    {
        $address = AddressHelper::toBase58($address);

        $data = $this->manager->request('wallet/getaccountresource', null, [
            'address' => AddressHelper::toHex($address),
        ]);

        return AccountResourcesDTO::fromArray($address, $data);
    }

    public function freezeBalanceV2(TronAddress $tronAddress, float $amount, string $resource = 'ENERGY')
    {
        $data = $this->manager->request('wallet/freezebalancev2', null, [
            'owner_address'  => AddressHelper::toHex($tronAddress->address),
            'frozen_balance' => AmountHelper::decimalToSun($amount),
            'resource'       => $resource
        ]);

        $signedTransaction = $this->signTransaction($data, $tronAddress->private_key);

        $data = $this->manager->request('wallet/broadcasttransaction', null, $signedTransaction);
        if (!isset($data['txid'])) {
            throw new BadResponseException($response['Error'] ?? print_r($data, true));
        }

        return $data;
    }

    public function unfreezeBalanceV2(TronAddress $tronAddress, float $amount, string $resource = 'ENERGY')
    {
        $data = $this->manager->request('wallet/unfreezebalancev2', null, [
            'owner_address'  => AddressHelper::toHex($tronAddress->address),
            'unfreeze_balance' => AmountHelper::decimalToSun($amount),
            'resource' => $resource
        ]);

        $signedTransaction = $this->signTransaction($data, $tronAddress->private_key);

        $data = $this->manager->request('wallet/broadcasttransaction', null, $signedTransaction);
        if (!isset($data['txid'])) {
            throw new BadResponseException($response['Error'] ?? print_r($data, true));
        }

        return $data;
    }

    public function delegateResource(TronAddress $tronAddress, string $toAddress, float $amount, string $resource = 'ENERGY')
    {
        $data = $this->manager->request('wallet/delegateresource', null, [
            'owner_address'  => AddressHelper::toHex($tronAddress->address),
            'receiver_address' => AddressHelper::toHex($toAddress),
            'balance' => AmountHelper::decimalToSun($amount),
            'resource' => $resource
        ]);

        $signedTransaction = $this->signTransaction($data, $tronAddress->private_key);

        $data = $this->manager->request('wallet/broadcasttransaction', null, $signedTransaction);
        if (!isset($data['txid'])) {
            throw new BadResponseException($response['Error'] ?? print_r($data, true));
        }

        return $data;
    }

    public function undelegateResource(TronAddress $tronAddress, string $toAddress, float $amount, string $resource = 'ENERGY')
    {
        $data = $this->manager->request('wallet/undelegateresource', null, [
            'owner_address'  => AddressHelper::toHex($tronAddress->address),
            'receiver_address' => AddressHelper::toHex($toAddress),
            'balance' => AmountHelper::decimalToSun($amount),
            'resource' => $resource
        ]);

        $signedTransaction = $this->signTransaction($data, $tronAddress->private_key);

        $data = $this->manager->request('wallet/broadcasttransaction', null, $signedTransaction);
        if (!isset($data['txid'])) {
            throw new BadResponseException($response['Error'] ?? print_r($data, true));
        }

        return $data;
    }

    public function getDelegatedResourceAccountIndexV2(string $value)
    {
        $data = $this->manager->request('wallet/getdelegatedresourceaccountindexv2', null, [
            'value'  => AddressHelper::toHex($value),
        ]);

        return $data;
    }

    public function getDelegatedResourceV2(string $fromAddress, string $toAddress)
    {
        $data = $this->manager->request('wallet/getdelegatedresourcev2', null, [
            'fromAddress'  => AddressHelper::toHex($fromAddress),
            'toAddress'  => AddressHelper::toHex($toAddress),
        ]);

        return $data;
    }

    public function getAvailableUnfreezeCount(string $ownerAddress)
    {
        $data = $this->manager->request('wallet/getavailableunfreezecount', null, [
            'owner_address'  => AddressHelper::toHex($ownerAddress),
        ]);

        return $data;
    }

    public function getCanWithdrawUnfreezeAmount(TronAddress $tronAddress, $timestampMs = null)
    {
        if ($timestampMs == null) {
            $timestampMs = Carbon::now()->getTimestampMs();
        }
        $data = $this->manager->request('wallet/getcanwithdrawunfreezeamount', null, [
            'owner_address'  => AddressHelper::toHex($tronAddress->address),
            'timestamp' => $timestampMs
        ]);

        return $data;
    }

    public function withdrawExpireUnfreeze(TronAddress $tronAddress)
    {
        $data = $this->manager->request('wallet/withdrawexpireunfreeze', null, [
            'owner_address'  => AddressHelper::toHex($tronAddress->address),
        ]);

        $signedTransaction = $this->signTransaction($data, $tronAddress->private_key);

        $data = $this->manager->request('wallet/broadcasttransaction', null, $signedTransaction);
        if (!isset($data['txid'])) {
            throw new BadResponseException($response['Error'] ?? print_r($data, true));
        }

        return $data;
    }

    public function validateAddress(string $address, string &$error = null): bool
    {
        $data = $this->manager->request('wallet/validateaddress', null, [
            'address' => $address,
        ]);
        if (!($data['result'] ?? false)) {
            $error = $data['message'] ?? print_r($data, true);
            return false;
        }

        return true;
    }

    public function getTransactions(string $address): Transactions
    {
        $address = AddressHelper::toBase58($address);

        return new Transactions($this->manager, $address);
    }

    public function getTRC20Transfers(string $address): TRC20Transfers
    {
        $address = AddressHelper::toBase58($address);

        return new TRC20Transfers($this->manager, $address);
    }

    public function getTRC20Contract(string $contractAddress): TRC20Contract
    {
        $contractAddress = AddressHelper::toBase58($contractAddress);

        return new TRC20Contract($this->manager, $contractAddress);
    }

    public function getTransactionInfo(string $txid): TransactionInfoDTO
    {
        $data = $this->manager->request('wallet/gettransactioninfobyid', null, [
            'value' => $txid
        ]);
        if (count($data) === 0) {
            throw new \Exception('Transaction '.$txid.' not found');
        }

        return TransactionInfoDTO::fromArray($data);
    }

    public function getTransfer(string $txid): ?TransferDTO
    {
        $data = $this->manager->request('wallet/gettransactionbyid', [
            'value' => $txid,
        ]);
        if (count($data) === 0) {
            throw new \Exception('Transaction '.$txid.' not found');
        }

        return TransferDTO::fromArray($data, true);
    }

    public function getTransferBlockNumber(string $txid): mixed
    {
        $data = $this->manager->request('wallet/gettransactioninfobyid', [
            'value' => $txid,
        ]);

        return $data['blockNumber'] ?? null;
    }

    public function transfer(string $from, string $to, string|int|float|BigDecimal $amount): Transfer
    {
        $from = AddressHelper::toBase58($from);
        $to = AddressHelper::toBase58($to);
        $amount = BigDecimal::of($amount);

        return new Transfer(
            api: $this,
            from: $from,
            to: $to,
            amount: $amount
        );
    }

    public function transferTRC20(
        string $contractAddress,
        string $from,
        string $to,
        string|int|float|BigDecimal $amount,
        string|int|float|BigDecimal $feeLimit = 30
    ): TRC20Transfer {
        $contract = $this->getTRC20Contract($contractAddress);
        $from = AddressHelper::toBase58($from);
        $to = AddressHelper::toBase58($to);
        $amount = BigDecimal::of($amount);
        $feeLimit = BigDecimal::of($feeLimit);

        return new TRC20Transfer(
            api: $this,
            contract: $contract,
            from: $from,
            to: $to,
            amount: $amount,
            feeLimit: $feeLimit,
        );
    }

    public function signTransaction(array $transaction, string $privateKey): array
    {
        $secp = new Secp256k1();

        /** @var Signature $sign */
        $sign = $secp->sign($transaction['txID'], $privateKey, ['canonical' => false]);
        $transaction['signature'] = $sign->toHex().bin2hex(implode('', array_map('chr', [$sign->getRecoveryParam()])));

        return $transaction;
    }
}
