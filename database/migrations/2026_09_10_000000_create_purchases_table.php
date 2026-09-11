<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('offer_id')->constrained('offers')->restrictOnDelete();
            $table->foreignId('retailer_id')->constrained('retailers')->restrictOnDelete();
            $table->foreignId('producer_id')->constrained('producers')->restrictOnDelete();
            $table->string('source_product_id');
            $table->text('product_name');
            $table->integer('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('total_price', 14, 2);
            $table->timestamps();

            $table->index(['retailer_id', 'created_at']);
            $table->index(['producer_id', 'created_at']);
            $table->index(['offer_id', 'created_at']);
        });

        DB::statement('ALTER TABLE purchases ADD CONSTRAINT purchases_quantity_positive CHECK (quantity > 0)');
        DB::statement('ALTER TABLE purchases ADD CONSTRAINT purchases_unit_price_positive CHECK (unit_price > 0)');
        DB::statement('ALTER TABLE purchases ADD CONSTRAINT purchases_total_price_positive CHECK (total_price > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
