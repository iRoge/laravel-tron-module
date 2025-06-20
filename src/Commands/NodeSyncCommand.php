<?php

namespace Iroge\LaravelTronModule\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Iroge\LaravelTronModule\Models\TronNode;
use Iroge\LaravelTronModule\Services\NodeSync;

class NodeSyncCommand extends Command
{
    protected $signature = 'tron:node-sync {node_id}';

    protected $description = 'Start Tron Node synchronization';

    public function handle(): void
    {
        $this->line('-- Starting sync Tron Node #'.$this->argument('node_id').' ...');

        /** @var class-string<TronNode> $model */
        $model = config('tron.models.node');
        $node = $model::findOrFail($this->argument('node_id'));

        $this->line('-- Node: *'.$node->name.'*'.$node->title);

        $service = App::make(NodeSync::class, [
            'node' => $node
        ]);

        $service->run();
    }
}
