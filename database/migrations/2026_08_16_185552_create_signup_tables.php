<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_login')->nullable();
            $table->boolean('is_retailer')->default(false);
            $table->boolean('is_producer')->default(false);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
        });

        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->string('neighborhood', 30);
            $table->string('street', 50);
            $table->string('city', 30);
            $table->string('state', 2);
            $table->string('zip', 10);
            $table->string('complement', 50)->nullable();
        });

        Schema::create('retailers', function (Blueprint $table) {
            $table->foreignId("user_id")->primary()->constrained("users")->cascadeOnDelete();
            $table->foreignId('address_id')->constrained('addresses');
            $table->string('identity', 14)->unique();
            $table->string('phone', 15);
        });

        Schema::create('producers', function (Blueprint $table) {
            $table->foreignId("user_id")->primary()->constrained("users")->cascadeOnDelete();
            $table->foreignId('address_id')->constrained('addresses');
            $table->string('identity', 14)->unique();
            $table->string('phone', 15);
        });
    }
};
