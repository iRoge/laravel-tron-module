<?php

namespace Iroge\LaravelTronModule;

use Iroge\LaravelTronModule\Commands\NewTRC20Command;
use Iroge\LaravelTronModule\Commands\NewWalletCommand;
use Iroge\LaravelTronModule\Commands\NewAddressCommand;
use Iroge\LaravelTronModule\Commands\ImportAddressCommand;
use Iroge\LaravelTronModule\Commands\AddressSyncCommand;
use Iroge\LaravelTronModule\Commands\NewNodeCommand;
use Iroge\LaravelTronModule\Commands\NodeSyncCommand;
use Iroge\LaravelTronModule\Commands\SyncCommand;
use Iroge\LaravelTronModule\Commands\WalletSyncCommand;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class TronServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('tron')
            ->hasConfigFile()
            ->hasMigrations([
                'create_tron_nodes_table',
                'create_tron_wallets_table',
                'create_tron_trc20_table',
                'create_tron_addresses_table',
                'create_tron_transactions_table',
                'create_tron_deposits_table',
                'create_tron_delegates_table',
            ])
            ->runsMigrations()
            ->hasCommands(
                NewNodeCommand::class,
                NewWalletCommand::class,
                NewAddressCommand::class,
                ImportAddressCommand::class,
                NewTRC20Command::class,
                SyncCommand::class,
                AddressSyncCommand::class,
                WalletSyncCommand::class,
                NodeSyncCommand::class,
            )
            ->hasInstallCommand(function(InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->publishMigrations();
            });

        $this->app->singleton(Tron::class);
    }
}
