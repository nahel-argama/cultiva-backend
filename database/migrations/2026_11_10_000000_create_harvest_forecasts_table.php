<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('harvest_forecasts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('producer_id')->constrained()->cascadeOnDelete();
            $table->string('source_product_id');
            $table->string('product_name');
            $table->unsignedInteger('quantity');
            $table->date('start_date');
            $table->date('end_date');
            $table->timestamps();
            $table->index(['end_date', 'source_product_id']);
            $table->index(['producer_id', 'end_date']);
        });

        DB::statement('ALTER TABLE harvest_forecasts ADD CONSTRAINT harvest_forecasts_quantity_positive CHECK (quantity > 0)');
        DB::statement('ALTER TABLE harvest_forecasts ADD CONSTRAINT harvest_forecasts_date_range_valid CHECK (start_date <= end_date)');
    }

    public function down(): void
    {
        Schema::dropIfExists('harvest_forecasts');
    }
};
