<?php

return [
    /*
     * Touch Synchronization System (TSS) config
     * If there are many addresses in the system, we synchronize only those that have been touched recently.
     * You must update touch_at in TronAddress, if you want sync here.
     */
    'touch' => [
        /*
         * Is system enabled?
         */
        'enabled' => false,

        /*
         * The time during which the address is synchronized after touching it (in seconds).
         */
        'waiting_seconds' => 3600,
    ],

    /*
     * Sets the handler to be used when Tron Wallet
     * receives a new deposit.
     */
    'webhook_handler' => \Iroge\LaravelTronModule\Handlers\EmptyWebhookHandler::class,

    /*
     * Set model class for both TronWallet, TronAddress, TronTrc20,
     * to allow more customization.
     *
     * TronApi model must be or extend `Iroge\LaravelTronModule\Api\Api::class`
     * TronNode model must be or extend `Iroge\LaravelTronModule\Models\TronNode::class`
     * TronWallet model must be or extend `Iroge\LaravelTronModule\Models\TronWallet::class`
     * TronAddress model must be or extend `Iroge\LaravelTronModule\Models\TronAddress::class`
     * TronTrc20 model must be or extend `Iroge\LaravelTronModule\Models\TronTrc20::class`
     * TronTransaction model must be or extend `Iroge\LaravelTronModule\Models\TronTransaction::class`
     * TronDeposit model must be or extend `Iroge\LaravelTronModule\Models\TronDeposit::class`
     */
    'models' => [
        'api' => \Iroge\LaravelTronModule\Api\Api::class,
        'node' => \Iroge\LaravelTronModule\Models\TronNode::class,
        'wallet' => \Iroge\LaravelTronModule\Models\TronWallet::class,
        'address' => \Iroge\LaravelTronModule\Models\TronAddress::class,
        'trc20' => \Iroge\LaravelTronModule\Models\TronTRC20::class,
        'transaction' => \Iroge\LaravelTronModule\Models\TronTransaction::class,
        'deposit' => \Iroge\LaravelTronModule\Models\TronDeposit::class,
        'delegate' => \Iroge\LaravelTronModule\Models\TronDelegate::class,
    ]
];
