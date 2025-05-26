<?php

namespace Iroge\LaravelTronModule\Enums;

enum TronTransactionType: string
{
    case INCOMING = 'in';
    case OUTGOING = 'out';
}
