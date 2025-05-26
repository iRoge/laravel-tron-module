<?php

namespace Iroge\LaravelTronModule\Api\Methods;

use Iroge\LaravelTronModule\Api\ApiManager;
use Iroge\LaravelTronModule\Api\DTO\Transaction\AbstractTransactionDTO;
use Iroge\LaravelTronModule\Api\DTO\Transaction\DelegateV2ResourcesTransactionDTO;
use Iroge\LaravelTronModule\Api\DTO\Transaction\FreezeBalanceV2TransactionDTO;
use Iroge\LaravelTronModule\Api\DTO\Transaction\TransferTransactionDTO;
use Iroge\LaravelTronModule\Api\DTO\Transaction\UnDelegateV2ResourcesTransactionDTO;
use Iroge\LaravelTronModule\Api\DTO\Transaction\UnFreezeBalanceV2TransactionDTO;
use Iroge\LaravelTronModule\Api\Enums\Confirmation;
use Iroge\LaravelTronModule\Api\Enums\Direction;
use Iroge\LaravelTronModule\Api\Enums\OrderBy;

class Transactions implements \Iterator
{
    protected ?bool $onlyConfirmation = null;
    protected ?bool $onlyDirection = null;
    protected int $limit = 20;
    protected ?string $fingerprint = null;
    protected ?string $orderBy = null;
    protected ?int $minTimestamp = null;
    protected ?int $maxTimestamp = null;
    protected bool $searchInterval = true;

    protected array $collection = [];
    protected bool $hasNext = true;
    protected int $current = 0;

    public function __construct(protected readonly ApiManager $manager, public readonly string $address)
    {
    }

    public function onlyConfirmation(?Confirmation $confirmed): static
    {
        $this->onlyConfirmation = $confirmed ? $confirmed->name === 'CONFIRMED' : null;

        return $this;
    }

    public function onlyDirection(?Direction $direction): static
    {
        $this->onlyDirection = $direction?->value;

        return $this;
    }

    public function limit(int $limit): static
    {
        $this->limit = max(min($limit, 200), 1);

        return $this;
    }

    public function orderBy(?OrderBy $orderBy): static
    {
        $this->orderBy = $orderBy ? 'block_timestamp,'.$orderBy->value : null;

        return $this;
    }

    public function minTimestamp(?int $minTimestamp): static
    {
        $this->minTimestamp = $minTimestamp;

        return $this;
    }

    public function maxTimestamp(?int $maxTimestamp): static
    {
        $this->maxTimestamp = $maxTimestamp;

        return $this;
    }

    public function searchInterval(bool $searchInterval): static
    {
        $this->searchInterval = $searchInterval;

        return $this;
    }

    public function getQuery(): array
    {
        $query = [
            'limit' => $this->limit,
            'fingerprint' => $this->fingerprint,
            'order_by' => $this->orderBy,
            'min_timestamp' => $this->minTimestamp,
            'max_timestamp' => $this->maxTimestamp,
            'search_internal' => $this->searchInterval,
        ];

        if ($this->onlyConfirmation !== null) {
            $query[$this->onlyConfirmation ? 'only_confirmed' : 'only_unconfirmed'] = true;
        }
        if ($this->onlyDirection !== null) {
            $query[$this->onlyDirection === Direction::FROM ? 'only_from' : 'only_to'] = true;
        }

        return array_filter($query, fn($item) => $item !== null);
    }

    public function current(): AbstractTransactionDTO
    {
        return $this->collection[$this->current];
    }

    public function next(): void
    {
        $this->current++;
    }

    public function key(): int
    {
        return $this->current;
    }

    public function valid(): bool
    {
        if (isset($this->collection[$this->current])) {
            return true;
        }

        if (!$this->hasNext) {
            return false;
        }

        $this->collection = array_values([
            ...$this->collection,
            ...$this->request(),
        ]);

        return isset($this->collection[$this->current]);
    }

    public function rewind(): void
    {
        $this->collection = $this->request();

        $this->current = 0;
    }

    protected function request(): array
    {
        $data = $this->manager->request(
            'v1/accounts/'.$this->address.'/transactions',
            $this->getQuery()
        );

        $this->fingerprint = $data['meta']['fingerprint'] ?? null;
        $this->hasNext = !!$this->fingerprint;

        return array_values(
            array_filter(
                array_map(
                    function (array $data) {
                        if (!isset($data['raw_data']['contract'][0]['type'])) {
                            return null;
                        }

                        if ($data['raw_data']['contract'][0]['type'] == 'TransferContract') {
                            return TransferTransactionDTO::fromArray($data);
                        } elseif ($data['raw_data']['contract'][0]['type'] == 'UnDelegateResourceContract') {
                            return UnDelegateV2ResourcesTransactionDTO::fromArray($data);
                        } elseif ($data['raw_data']['contract'][0]['type'] == 'DelegateResourceContract') {
                            return DelegateV2ResourcesTransactionDTO::fromArray($data);
                        } elseif ($data['raw_data']['contract'][0]['type'] == 'UnfreezeBalanceV2Contract') {
                            return UnFreezeBalanceV2TransactionDTO::fromArray($data);
                        } elseif ($data['raw_data']['contract'][0]['type'] == 'FreezeBalanceV2Contract') {
                            return FreezeBalanceV2TransactionDTO::fromArray($data);
                        }

                        return null;
                    },
                    $data['data']
                )
            )
        );
    }
}
