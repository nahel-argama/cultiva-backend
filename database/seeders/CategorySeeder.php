<?php

namespace Database\Seeders;

use Cultiva\Models\Category\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $timestamp = now();

        Category::upsert([
            ['id' => 1, 'name' => 'Frutas', 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['id' => 2, 'name' => 'Legumes', 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['id' => 3, 'name' => 'Verduras', 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['id' => 4, 'name' => 'Tubérculos e raízes', 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['id' => 5, 'name' => 'Grãos e cereais', 'created_at' => $timestamp, 'updated_at' => $timestamp],
        ], ['id'], ['name', 'updated_at']);
    }
}
