<?php

namespace Iroge\LaravelTronModule\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Iroge\LaravelTronModule\Services\TronSync;

class SyncCommand extends Command
{
    protected $signature = 'tron:sync';

    protected $description = 'Start Tron synchronization';

    public function handle(): void
    {
        Cache::lock('tron', 300)->get(function() {
            $this->line('---- Starting sync Tron...');

            $service = App::make(TronSync::class);

            $service->run();

            $this->line('---- Completed!');
        });
    }
}
