<?php

namespace Iroge\LaravelTronModule\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Iroge\LaravelTronModule\Models\TronAddress;
use Iroge\LaravelTronModule\Services\AddressSync;

class AddressSyncCommand extends Command
{
    protected $signature = 'tron:address-sync {address_id}';

    protected $description = 'Start Tron Address synchronization';

    public function handle(): void
    {
        try {
            /** @var class-string<TronAddress> $model */
            $model = config('tron.models.address');
            $address = $model::findOrFail($this->argument('address_id'));

            $service = App::make(AddressSync::class, [
                'address' => $address,
                'force' => true,
            ]);

            $service->run();
        } catch (\Exception $e) {
            $this->error('Error: '.$e->getMessage());
        }

        $this->line('Completed!');
    }
}
