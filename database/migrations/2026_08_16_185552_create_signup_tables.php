<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('profile_type');
            $table->timestamp('last_login')->nullable()->after('remember_token');
            $table->boolean('is_active')->default(true)->after('last_login');
            $table->softDeletes()->after('updated_at');
        });

        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->morphs('addressable');

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

        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();

            $table->string('document_number', 14)->unique();
            $table->string('trade_name', 100);
            $table->string('legal_name', 100)->nullable();
            $table->string('phone', 15);

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('producers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->unique()->constrained('companies')->cascadeOnDelete();

            $table->string('activity_segment', 30);

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('retailers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->unique()->constrained('companies')->cascadeOnDelete();

            $table->string('business_type', 30);

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->unique()->constrained('companies')->cascadeOnDelete();

            $table->string('cnh_number', 11)->unique();
            $table->string('cnh_category', 5);

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_id')->constrained('deliveries')->cascadeOnDelete();

            $table->string('plate', 8)->unique();
            $table->string('cargo_type', 30);

            $table->timestamps();
            $table->softDeletes();
        });
    }
};
