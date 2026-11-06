<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('golog_drivers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->unique()->constrained('companies')->cascadeOnDelete();
            $table->string('cpf', 11)->unique();
            $table->uuid('golog_user_id')->nullable()->unique();
            $table->uuid('golog_driver_id')->nullable()->unique();
            $table->uuid('golog_work_schedule_id')->nullable();
            $table->timestamps();
        });

        Schema::create('golog_vehicles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('golog_driver_id')->unique()->constrained('golog_drivers')->cascadeOnDelete();
            $table->uuid('golog_tractor_id')->nullable()->unique();
            $table->uuid('golog_equipament_group_id')->nullable()->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('golog_vehicles');
        Schema::dropIfExists('golog_drivers');
    }
};
