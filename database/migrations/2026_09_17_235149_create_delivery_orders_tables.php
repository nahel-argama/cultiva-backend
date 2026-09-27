<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_addresses', function (Blueprint $table): void {
            $table->id();
            $table->string('zip', 8);
            $table->string('street', 100);
            $table->string('number', 20);
            $table->string('complement', 50)->nullable();
            $table->string('reference_point', 150)->nullable();
            $table->string('neighborhood', 50);
            $table->string('city', 50);
            $table->string('state', 2);
            $table->geography('coordinate', subtype: 'point', srid: 4326)->nullable();
            $table->timestamps();
        });

        Schema::create('delivery_trips', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('delivery_id')->nullable()->constrained('deliveries')->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->foreignId('cargo_type_id')->constrained('cargo_types')->restrictOnDelete();
            $table->string('status', 20)->default('available');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['delivery_id', 'status']);
        });

        DB::statement("ALTER TABLE delivery_trips ADD CONSTRAINT delivery_trips_status_valid CHECK (status IN ('available', 'assigned', 'in_progress', 'completed', 'cancelled'))");

        Schema::create('delivery_trip_stops', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('trip_id')->constrained('delivery_trips')->cascadeOnDelete();
            $table->foreignId('delivery_address_id')->constrained('delivery_addresses')->restrictOnDelete();
            $table->integer('sequence');
            $table->string('stop_type', 10);
            $table->string('status', 20)->default('pending');
            $table->timestamp('arrived_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['trip_id', 'sequence']);
            $table->index(['trip_id', 'status']);
        });

        DB::statement('ALTER TABLE delivery_trip_stops ADD CONSTRAINT delivery_trip_stops_sequence_positive CHECK (sequence > 0)');
        DB::statement("ALTER TABLE delivery_trip_stops ADD CONSTRAINT delivery_trip_stops_type_valid CHECK (stop_type IN ('pickup', 'dropoff'))");
        DB::statement("ALTER TABLE delivery_trip_stops ADD CONSTRAINT delivery_trip_stops_status_valid CHECK (status IN ('pending', 'arrived', 'completed', 'skipped'))");

        Schema::create('delivery_orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('purchase_id')->constrained('purchases')->restrictOnDelete();
            $table->foreignId('trip_id')->nullable()->constrained('delivery_trips')->nullOnDelete();
            $table->foreignId('pickup_stop_id')->nullable()->constrained('delivery_trip_stops')->nullOnDelete();
            $table->foreignId('dropoff_stop_id')->nullable()->constrained('delivery_trip_stops')->nullOnDelete();
            $table->foreignId('pickup_address_id')->constrained('delivery_addresses')->restrictOnDelete();
            $table->foreignId('dropoff_address_id')->constrained('delivery_addresses')->restrictOnDelete();
            $table->integer('quantity');
            $table->string('status', 20)->default('pending');
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['purchase_id', 'status']);
            $table->index(['trip_id', 'status']);
            $table->index(['status', 'created_at']);
        });

        DB::statement('ALTER TABLE delivery_orders ADD CONSTRAINT delivery_orders_quantity_positive CHECK (quantity > 0)');
        DB::statement("ALTER TABLE delivery_orders ADD CONSTRAINT delivery_orders_status_valid CHECK (status IN ('pending', 'assigned', 'in_transit', 'delivered', 'failed', 'cancelled'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_orders');
        Schema::dropIfExists('delivery_trip_stops');
        Schema::dropIfExists('delivery_trips');
        Schema::dropIfExists('delivery_addresses');
    }
};
