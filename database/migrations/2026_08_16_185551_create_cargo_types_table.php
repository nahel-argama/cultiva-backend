<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cargo_types', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 50)->unique();
            $table->timestamps();
        });

        $timestamp = now();

        DB::table('cargo_types')->insert([
            ['code' => 'dry', 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['code' => 'climate_controlled', 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['code' => 'refrigerated', 'created_at' => $timestamp, 'updated_at' => $timestamp],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('cargo_types');
    }
};
