<?php

namespace Iroge\LaravelTronModule;

use Illuminate\Database\Eloquent\Model;
use Iroge\LaravelTronModule\Api\Api;
use Iroge\LaravelTronModule\Concerns\Address;
use Iroge\LaravelTronModule\Concerns\Mnemonic;
use Iroge\LaravelTronModule\Concerns\Node;
use Iroge\LaravelTronModule\Concerns\Transfer;
use Iroge\LaravelTronModule\Concerns\TRC20;
use Iroge\LaravelTronModule\Concerns\Wallet;
use Iroge\LaravelTronModule\Enums\TronModel;
use Iroge\LaravelTronModule\Models\TronNode;

class Tron
{
    use Node, Mnemonic, Wallet, Address, TRC20, Transfer;

    /**
     * @param TronModel $model
     * @return class-string<Model>
     */
    public function getModel(TronModel $model): string
    {
        return config('tron.models.'.$model->value);
    }

    /**
     * @return class-string<Api>
     */
    public function getApi(): string
    {
        return config('tron.models.api');
    }

    public function getNode(): TronNode
    {
        /** @var TronNode $node */
        $node = $this->getModel(TronModel::Node)::query()
            ->where('worked', '=', true)
            ->where('available', '=', true)
            ->orderBy('requests')
            ->firstOrFail();

        return $node;
    }
}
