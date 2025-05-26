<?php

namespace Iroge\LaravelTronModule\Handlers;

use Iroge\LaravelTronModule\Models\TronAddress;
use Iroge\LaravelTronModule\Models\TronDeposit;
use Iroge\LaravelTronModule\Models\TronTransaction;

interface WebhookHandlerInterface
{
    public function handle(TronDeposit $deposit): void;
}
