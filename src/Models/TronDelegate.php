<?php

namespace Iroge\LaravelTronModule\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Iroge\LaravelTronModule\Casts\ResourceType;

class TronDelegate extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'owner_address',
        'receiver_address',
        'amount',
        'resource',
    ];


    protected $casts = [
        'resource' => ResourceType::class,
    ];


    public function ownerAddress(): BelongsTo
    {
        /** @var class-string<TronAddress> $addressModel */
        $addressModel = config('tron.models.address');

        return $this->belongsTo($addressModel, 'address', 'owner_address');
    }

    public function receiverAddress(): BelongsTo
    {
        /** @var class-string<TronAddress> $addressModel */
        $addressModel = config('tron.models.address');

        return $this->belongsTo($addressModel, 'address', 'receiver_address');
    }
}
