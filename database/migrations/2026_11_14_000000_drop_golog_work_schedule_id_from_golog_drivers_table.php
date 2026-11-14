<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('golog_drivers', function (Blueprint $table): void {
            $table->dropColumn('golog_work_schedule_id');
        });
    }

    public function down(): void
    {
        Schema::table('golog_drivers', function (Blueprint $table): void {
            $table->uuid('golog_work_schedule_id')->nullable()->after('golog_driver_id');
        });
    }
};
