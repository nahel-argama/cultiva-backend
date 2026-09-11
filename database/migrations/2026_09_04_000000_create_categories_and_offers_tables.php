<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100)->unique();
            $table->timestamps();
        });

        Schema::create('offers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('producer_id')->constrained('producers')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->string('source_product_id');
            $table->text('product_name');
            $table->decimal('unit_price', 12, 2);
            $table->integer('total_quantity');
            $table->integer('reserved_quantity')->default(0);
            $table->string('status', 8)->default('inactive');
            $table->timestamps();

            $table->index(['producer_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });

        DB::statement('ALTER TABLE offers ADD CONSTRAINT offers_unit_price_positive CHECK (unit_price > 0)');
        DB::statement('ALTER TABLE offers ADD CONSTRAINT offers_total_quantity_non_negative CHECK (total_quantity >= 0)');
        DB::statement('ALTER TABLE offers ADD CONSTRAINT offers_reserved_quantity_non_negative CHECK (reserved_quantity >= 0)');
        DB::statement('ALTER TABLE offers ADD CONSTRAINT offers_reserved_not_above_total CHECK (reserved_quantity <= total_quantity)');
        DB::statement("ALTER TABLE offers ADD CONSTRAINT offers_status_valid CHECK (status IN ('active', 'inactive'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('offers');
        Schema::dropIfExists('categories');
    }
};
