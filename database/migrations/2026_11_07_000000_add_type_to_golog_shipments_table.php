<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('golog_shipments', function (Blueprint $table): void {
            $table->string('type', 20)->default('ENTREGA')->after('golog_shipment_id');
            $table->dropUnique('golog_shipments_delivery_order_id_unique');
            $table->unique(['delivery_order_id', 'type']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::table('golog_shipments', function (Blueprint $table): void {
            $table->dropUnique(['delivery_order_id', 'type']);
            $table->dropIndex(['type']);
            $table->dropColumn('type');
            $table->unique('delivery_order_id');
        });
    }
};
