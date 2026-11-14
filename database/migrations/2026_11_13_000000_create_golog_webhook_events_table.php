<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('golog_webhook_events', function (Blueprint $table): void {
            $table->id();
            $table->uuid('delivery_id')->unique();
            $table->string('event', 50);
            $table->string('golog_shipment_id', 100)->nullable();
            $table->string('occurrence_id', 100)->nullable();
            $table->string('occurrence_type', 100)->nullable();
            $table->text('description')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->jsonb('payload');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index('golog_shipment_id');
            $table->index('event');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('golog_webhook_events');
    }
};
