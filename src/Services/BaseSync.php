<?php

namespace Iroge\LaravelTronModule\Services;

use Psr\Log\LoggerInterface;

abstract class BaseSync
{
    protected ?LoggerInterface $logger = null;
    protected float $startedAt;

    public function setLogger(?LoggerInterface $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    protected function log(string $message, ?string $type = null): void
    {
        if ($this->logger) {
            $this->logger->info($message);
        }
    }

    public function run(): void
    {
        $this->startedAt = microtime(true);
    }

}