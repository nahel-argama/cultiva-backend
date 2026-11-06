<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('golog_shipments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('delivery_order_id')->constrained('delivery_orders')->cascadeOnDelete();
            $table->string('golog_shipment_id', 100);
            $table->string('status', 50)->default('PENDENTE');
            $table->timestamps();

            $table->unique('delivery_order_id');
            $table->index('golog_shipment_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('golog_shipments');
    }
};
