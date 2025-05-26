<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Iroge\LaravelTronModule\Enums\TronTransactionType;
use Iroge\LaravelTronModule\Models\TronTRC20;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tron_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('txid')
                ->index();
            $table->unsignedSmallInteger('type');
            $table->timestamp('time_at');
            $table->string('from')->index();
            $table->string('to')->nullable()->index();
            $table->decimal('amount', 20, 6);
            $table->string('trc20_contract_address')
                ->nullable();
            $table->unsignedBigInteger('block_number')
                ->nullable();
            $table->json('debug_data');

            $table->unique(['txid']);
            $table->index(['from', 'type']);
            $table->index(['to', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tron_transactions');
    }
};
