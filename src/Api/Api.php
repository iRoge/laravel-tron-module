<?php

namespace Iroge\LaravelTronModule\Api;

use Brick\Math\BigDecimal;
use Iroge\LaravelTronModule\Api\DTO\AccountDTO;
use Iroge\LaravelTronModule\Api\DTO\AccountResourcesDTO;
use Iroge\LaravelTronModule\Api\DTO\BlockDTO;
use Iroge\LaravelTronModule\Api\DTO\Event\AbstractEventDTO;
use Iroge\LaravelTronModule\Api\DTO\Event\TransferEventDTO;
use Iroge\LaravelTronModule\Api\DTO\Event\UnknownEventDTO;
use Iroge\LaravelTronModule\Api\DTO\IDTO;
use Iroge\LaravelTronModule\Api\DTO\Transaction\DelegateV2ResourcesTransactionDTO;
use Iroge\LaravelTronModule\Api\DTO\Transaction\FreezeBalanceV2TransactionDTO;
use Iroge\LaravelTronModule\Api\DTO\Transaction\TransferTransactionDTO;
use Iroge\LaravelTronModule\Api\DTO\Transaction\TriggerSmartContractDto;
use Iroge\LaravelTronModule\Api\DTO\Transaction\UnDelegateV2ResourcesTransactionDTO;
use Iroge\LaravelTronModule\Api\DTO\Transaction\UnFreezeBalanceV2TransactionDTO;
use Iroge\LaravelTronModule\Api\DTO\Transaction\UnknownTransactionDto;
use Iroge\LaravelTronModule\Api\DTO\TransactionInfoDTO;
use Iroge\LaravelTronModule\Api\DTO\TransferDTO;
use Iroge\LaravelTronModule\Api\Exceptions\BadResponseException;
use Iroge\LaravelTronModule\Api\Helpers\AddressHelper;
use Iroge\LaravelTronModule\Api\Helpers\AmountHelper;
use Iroge\LaravelTronModule\Api\Methods\Transactions;
use Iroge\LaravelTronModule\Api\Methods\Transfer;
use Iroge\LaravelTronModule\Api\Methods\TRC20Transfer;
use Iroge\LaravelTronModule\Api\Methods\TRC20Transfers;
use Iroge\LaravelTronModule\Models\TronAddress;
use kornrunner\Secp256k1;
use kornrunner\Signature\Signature;

class Api
{
    public readonly ApiManager $manager;
    protected readonly array $chainParameters;

    private static array $transactionTypeDtoMap = [
        'TransferContract' => TransferTransactionDTO::class,
        'TriggerSmartContract' => TriggerSmartContractDto::class,
        'UnDelegateResourceContract' => UnDelegateV2ResourcesTransactionDTO::class,
        'DelegateResourceContract' => DelegateV2ResourcesTransactionDTO::class,
        'UnFreezeBalanceV2TransactionDTO' => UnFreezeBalanceV2TransactionDTO::class,
        'FreezeBalanceV2Contract' => FreezeBalanceV2TransactionDTO::class,
    ];

    private static array $eventTypeDtoMap = [
        'Transfer' => TransferEventDTO::class,
    ];

    public function __construct(
        ?HttpProvider $fullNode = null,
        ?HttpProvider $solidityNode = null,
        ?HttpProvider $eventServer = null,
        ?HttpProvider $signServer = null,
        ?HttpProvider $explorer = null,
    )
    {
        $this->manager = new ApiManager(
            compact(
                'fullNode',
                'solidityNode',
                'eventServer',
                'signServer',
                'explorer'
            )
        );

        $chainParametersFromFile = file_get_contents(__DIR__ . '/Resources/chain_parameters.json');
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
            'owner_address' => AddressHelper::toHex($tronAddress->address),
            'frozen_balance' => AmountHelper::decimalToSun($amount),
            'resource' => $resource
        ]);

        return $this->signAndBroadcastTransaction($data, $tronAddress->private_key);
    }

    public function unfreezeBalanceV2(TronAddress $tronAddress, float $amount, string $resource = 'ENERGY')
    {
        $data = $this->manager->request('wallet/unfreezebalancev2', null, [
            'owner_address' => AddressHelper::toHex($tronAddress->address),
            'unfreeze_balance' => AmountHelper::decimalToSun($amount),
            'resource' => $resource
        ]);

        return $this->signAndBroadcastTransaction($data, $tronAddress->private_key);
    }

    public function delegateResource(TronAddress $tronAddress, string $toAddress, float $amount, string $resource = 'ENERGY')
    {
        $data = $this->manager->request('wallet/delegateresource', null, [
            'owner_address' => AddressHelper::toHex($tronAddress->address),
            'receiver_address' => AddressHelper::toHex($toAddress),
            'balance' => AmountHelper::decimalToSun($amount),
            'resource' => $resource
        ]);

        return $this->signAndBroadcastTransaction($data, $tronAddress->private_key);
    }

    public function undelegateResource(TronAddress $tronAddress, string $toAddress, float $amount, string $resource = 'ENERGY')
    {
        $data = $this->manager->request('wallet/undelegateresource', null, [
            'owner_address' => AddressHelper::toHex($tronAddress->address),
            'receiver_address' => AddressHelper::toHex($toAddress),
            'balance' => AmountHelper::decimalToSun($amount),
            'resource' => $resource
        ]);

        return $this->signAndBroadcastTransaction($data, $tronAddress->private_key);
    }

    public function createTransaction($fromAddress, string $toAddress, BigDecimal $amount)
    {
        return $this->manager->request('wallet/createtransaction', null, [
            'owner_address' => AddressHelper::toHex($fromAddress),
            'to_address' => AddressHelper::toHex($toAddress),
            'amount' => AmountHelper::decimalToSun($amount),
        ]);
    }

    public function triggerSmartContract(
        $ownerAddress,
        $contractAddress,
        $functionSelector,
        $parameters,
        $feeLimit,
        $cellValue
    )
    {
        return $this->manager->request('wallet/triggersmartcontract', null, [
            'owner_address' => $ownerAddress,
            'contract_address' => $contractAddress,
            'function_selector' => $functionSelector,
            'parameter' => $parameters,
            'fee_limit' => $feeLimit,
            'call_value' => $cellValue,
        ]);
    }

    public function triggerConstantContract(string $ownerAddress, string $contractAddress, string $functionSelector, string $parameters)
    {
        return $this->manager->request('wallet/triggerconstantcontract', null, [
            'owner_address' => $ownerAddress,
            'contract_address' => $contractAddress,
            'function_selector' => $functionSelector,
            'parameter' => $parameters,
        ]);
    }

    public function trc20Transfers(string $address, array $query)
    {
        return $this->manager->request(
            'v1/accounts/' . $address . '/transactions/trc20',
            $query
        );
    }

    public function transactions(string $address, array $query)
    {
        return $this->manager->request(
            'v1/accounts/' . $address . '/transactions',
            $query
        );
    }

    public function getDelegatedResourceAccountIndexV2(string $value)
    {
        $data = $this->manager->request('wallet/getdelegatedresourceaccountindexv2', null, [
            'value' => AddressHelper::toHex($value),
        ]);

        return $data;
    }

    public function getDelegatedResourceV2(string $fromAddress, string $toAddress)
    {
        $data = $this->manager->request('wallet/getdelegatedresourcev2', null, [
            'fromAddress' => AddressHelper::toHex($fromAddress),
            'toAddress' => AddressHelper::toHex($toAddress),
        ]);

        return $data;
    }

    public function getAvailableUnfreezeCount(string $ownerAddress)
    {
        $data = $this->manager->request('wallet/getavailableunfreezecount', null, [
            'owner_address' => AddressHelper::toHex($ownerAddress),
        ]);

        return $data;
    }

    public function getCanWithdrawUnfreezeAmount(TronAddress $tronAddress, $timestampMs = null)
    {
        if ($timestampMs == null) {
            $timestampMs = Carbon::now()->getTimestampMs();
        }
        $data = $this->manager->request('wallet/getcanwithdrawunfreezeamount', null, [
            'owner_address' => AddressHelper::toHex($tronAddress->address),
            'timestamp' => $timestampMs
        ]);

        return $data;
    }

    public function withdrawExpireUnfreeze(TronAddress $tronAddress)
    {
        $data = $this->manager->request('wallet/withdrawexpireunfreeze', null, [
            'owner_address' => AddressHelper::toHex($tronAddress->address),
        ]);

        return $this->signAndBroadcastTransaction($data, $tronAddress->private_key);
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

        return new Transactions($this, $address);
    }

    public function getTRC20Transfers(string $address): TRC20Transfers
    {
        $address = AddressHelper::toBase58($address);

        return new TRC20Transfers($this, $address);
    }

    public function getTRC20Contract(string $contractAddress): TRC20Contract
    {
        $contractAddress = AddressHelper::toBase58($contractAddress);

        return new TRC20Contract($this, $contractAddress);
    }

    public function getTransactionInfo(string $txid): TransactionInfoDTO
    {
        $data = $this->manager->request('wallet/gettransactioninfobyid', null, [
            'value' => $txid
        ]);
        if (count($data) === 0) {
            throw new \Exception('Transaction ' . $txid . ' not found');
        }

        return TransactionInfoDTO::fromArray($data);
    }

    public function getTransfer(string $txid): ?TransferDTO
    {
        $data = $this->manager->request('wallet/gettransactionbyid', [
            'value' => $txid,
        ]);
        if (count($data) === 0) {
            throw new \Exception('Transaction ' . $txid . ' not found');
        }

        return TransferDTO::fromArray($data, true);
    }

    public function getNowBlock(): BlockDTO
    {
        $data = $this->manager->request('walletsolidity/getblock', null, [
            'detail' => true
        ]);
        if (count($data) === 0) {
            throw new \Exception('Error while getting block: ' . print_r($data, true));
        }

        return BlockDTO::fromArray($data);
    }

    public function getBlockByNumber(int $number): ?BlockDTO
    {
        $data = $this->manager->request('walletsolidity/getblockbynum', null, [
            'num' => $number,
        ]);
        if (count($data) === 0) {
            return null;
        }

        return BlockDTO::fromArray($data);
    }

    /** @return array<AbstractEventDTO> */
    public function getEventsByBlockNumber(int $blockNumber): ?array
    {
        $limit = 200;
        $path = '/v1/blocks/' . $blockNumber . '/events';
        $responseArray = $this->manager->request($path, [
            'limit' => $limit
        ]);
        if (!$responseArray) {
            throw new \Exception('Error while getting events: ' . print_r($responseArray, true));
        }

        $events = $responseArray['data'];
        if (count($events) === 0) {
            return [];
        }

        while (isset($responseArray['meta']['fingerprint']) && count($events) % $limit === 0) {
            $responseArray = $this->manager->request($path, [
                'limit' => $limit,
                'fingerprint' => $responseArray['meta']['fingerprint']
            ]);
            if (!$responseArray) {
                break;
            }
            if (!count($responseArray['data'])) {
                break;
            }
            $events = array_merge($events, $responseArray['data']);
        }

        $array = [];
        foreach ($events as $event) {
            $array[] = self::getDtoByEventArray($event);
        }

        return $array;
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
        string                      $contractAddress,
        string                      $from,
        string                      $to,
        string|int|float|BigDecimal $amount,
        string|int|float|BigDecimal $feeLimit = 30
    ): TRC20Transfer
    {
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

    public function signAndBroadcastTransaction(array $data, string $privateKey): array
    {
        $signedTransaction = $this->signTransaction($data, $privateKey);

        $data = $this->manager->request('wallet/broadcasttransaction', null, $signedTransaction);

        if (!isset($data['txid'])) {
            throw new BadResponseException($response['Error'] ?? print_r($data, true));
        }

        return $data;
    }

    public function signTransaction(array $transaction, string $privateKey): array
    {
        $secp = new Secp256k1();

        /** @var Signature $sign */
        $sign = $secp->sign($transaction['txID'], $privateKey, ['canonical' => false]);
        $transaction['signature'] = $sign->toHex() . bin2hex(implode('', array_map('chr', [$sign->getRecoveryParam()])));

        return $transaction;
    }

    public static function getDtoByTransactionArray($array): ?IDTO
    {
        if (!isset($array['raw_data']['contract'][0]['type'])) {
            return null;
        }

        $type = $array['raw_data']['contract'][0]['type'];

        if (!isset(self::$transactionTypeDtoMap[$type])) {
            return UnknownTransactionDto::fromArray($array);
        }

        return self::$transactionTypeDtoMap[$type]::fromArray($array);
    }

    public static function getDtoByEventArray($array): ?IDTO
    {
        if (!isset($array['event_name'])) {
            return null;
        }

        $name = $array['event_name'];

        if (!isset(self::$eventTypeDtoMap[$name])) {
            return UnknownEventDTO::fromArray($array);
        }

        return self::$eventTypeDtoMap[$name]::fromArray($array);
    }
}
