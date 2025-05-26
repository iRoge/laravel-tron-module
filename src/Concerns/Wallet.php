<?php

namespace Iroge\LaravelTronModule\Concerns;

use Iroge\LaravelTronModule\Enums\TronModel;
use Iroge\LaravelTronModule\Facades\Tron;
use Iroge\LaravelTronModule\Models\TronNode;
use Iroge\LaravelTronModule\Models\TronWallet;

trait Wallet
{
    public function importWallet(
        string $name,
        string|array $mnemonic,
        ?string $passphrase = null,
        ?string $password = null,
        ?bool $savePassword = true,
        ?TronNode $node = null,
    ): TronWallet {
        if (is_array($mnemonic)) {
            $mnemonic = implode(" ", $mnemonic);
        }

        $seed = Tron::mnemonicSeed($mnemonic, $passphrase);

        /** @var class-string<TronWallet> $walletModel */
        $walletModel = Tron::getModel(TronModel::Wallet);

        $wallet = new $walletModel([
            'node_id' => $node?->id,
            'name' => $name,
        ]);
        $wallet->unlockWallet($password);
        if ($savePassword) {
            $wallet->password = $password;
        }
        $wallet->mnemonic = $mnemonic;
        $wallet->seed = $seed;

        return $wallet;
    }

    public function generateWallet(
        string $name,
        ?int $mnemonicSize = 18,
        ?string $passphrase = null,
        ?string $password = null,
        ?bool $savePassword = true,
        ?TronNode $node = null,
    ): TronWallet {
        $mnemonic = Tron::mnemonicGenerate($mnemonicSize ?? 18);
        $seed = Tron::mnemonicSeed($mnemonic, $passphrase);

        /** @var class-string<TronWallet> $walletModel */
        $walletModel = Tron::getModel(TronModel::Wallet);

        $wallet = new $walletModel([
            'node_id' => $node?->id,
            'name' => $name,
        ]);
        $wallet->unlockWallet($password);
        if ($savePassword) {
            $wallet->password = $password;
        }
        $wallet->mnemonic = implode(" ", $mnemonic);
        $wallet->seed = $seed;

        return $wallet;
    }

    public function newWallet(
        string $name,
        ?string $password = null,
        ?bool $savePassword = true,
        ?TronNode $node = null,
    ): TronWallet {
        /** @var class-string<TronWallet> $walletModel */
        $walletModel = Tron::getModel(TronModel::Wallet);

        $wallet = new $walletModel([
            'node_id' => $node?->id,
            'name' => $name,
        ]);
        $wallet->unlockWallet($password);
        if ($savePassword) {
            $wallet->password = $password;
        }

        return $wallet;
    }

    public function createWallet(
        string $name,
        ?string $password = null,
        ?bool $savePassword = true,
        string|array|int|null $mnemonic = null,
        ?string $passphrase = null,
        ?TronNode $node = null,
    ): TronWallet {
        if (is_string($mnemonic)) {
            $mnemonic = explode(' ', $mnemonic);
        } elseif (is_null($mnemonic) || is_int($mnemonic)) {
            $mnemonic = Tron::mnemonicGenerate($mnemonic ?? 18);
        }

        $seed = Tron::mnemonicSeed($mnemonic, $passphrase);

        /** @var class-string<TronWallet> $walletModel */
        $walletModel = Tron::getModel(TronModel::Wallet);

        $wallet = new $walletModel([
            'node_id' => $node?->id,
            'name' => $name,
        ]);
        $wallet->unlockWallet($password);
        if ($savePassword) {
            $wallet->password = $password;
        }
        $wallet->mnemonic = implode(" ", $mnemonic);
        $wallet->seed = $seed;
        $wallet->save();

        Tron::createAddress($wallet, 'Primary Address', 0);

        return $wallet;
    }
}
