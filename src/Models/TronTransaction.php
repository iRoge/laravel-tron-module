<?php

namespace Iroge\LaravelTronModule\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Iroge\LaravelTronModule\Casts\BigDecimalCast;
use Iroge\LaravelTronModule\Casts\TransactionType;

class TronTransaction extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'txid',
        'type',
        'time_at',
        'from',
        'to',
        'amount',
        'trc20_contract_address',
        'block_number',
        'debug_data',
    ];

    protected $appends = [
        'symbol'
    ];

    protected $casts = [
        'type' => TransactionType::class,
        'time_at' => 'datetime',
        'amount' => BigDecimalCast::class,
        'block_number' => 'integer',
        'debug_data' => 'json',
    ];


    public function fromAddress(): BelongsTo
    {
        /** @var class-string<TronAddress> $addressModel */
        $addressModel = config('tron.models.address');

        return $this->belongsTo($addressModel, 'address', 'from');
    }

    public function toAddress(): BelongsTo
    {
        /** @var class-string<TronAddress> $addressModel */
        $addressModel = config('tron.models.address');

        return $this->belongsTo($addressModel, 'address', 'to');
    }

    public function wallet(): HasOneThrough
    {
        /** @var class-string<TronWallet> $walletModel */
        $walletModel = config('tron.models.wallet');

        /** @var class-string<TronAddress> $addressModel */
        $addressModel = config('tron.models.address');

        return $this->hasOneThrough(
            $walletModel,
            $addressModel,
            'address',
            'id',
            'address',
            'wallet_id'
        );
    }

    public function trc20(): BelongsTo
    {
        return $this->belongsTo(TronTRC20::class, 'trc20_contract_address', 'address');
    }

    protected function symbol(): Attribute
    {
        return new Attribute(
            get: fn () => $this->trc20_contract_address ? ($this->trc20?->symbol ?: 'TOKEN') : 'TRX'
        );
    }
}
