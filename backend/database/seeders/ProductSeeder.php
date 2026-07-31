<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['AXXER Optical', 'DIRETOCOM', 'PATIFI'] as $name) {
            Product::firstOrCreate(['name' => $name], ['is_active' => true]);
        }
    }
}
