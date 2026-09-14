<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wishlist_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('retailer_id')->constrained('retailers')->cascadeOnDelete();
            $table->string('source_product_id');
            $table->text('product_name');
            $table->string('state', 2);
            $table->timestamps();

            $table->unique(['retailer_id', 'source_product_id']);
            $table->index(['retailer_id', 'created_at']);
            $table->index(['state', 'source_product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wishlist_items');
    }
};
