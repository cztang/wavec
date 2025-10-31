<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $products = [
            [
                'name' => 'iPhone 15 Pro',
                'sku' => 'APPL-IP15P-256',
            ],
            [
                'name' => 'MacBook Pro M3',
                'sku' => 'APPL-MBP-M3-14',
            ],
            [
                'name' => 'Samsung Galaxy S24 Ultra',
                'sku' => 'SAMS-GS24U-512',
            ],
            [
                'name' => 'Nike Air Jordan 1',
                'sku' => 'NIKE-AJ1-BLK-10',
            ],
            [
                'name' => 'Sony PlayStation 5',
                'sku' => 'SONY-PS5-STD',
            ],
            [
                'name' => 'Tesla Model Y Performance',
                'sku' => 'TSLA-MY-PERF',
            ],
            [
                'name' => 'Rolex Submariner',
                'sku' => 'RLXS-SUB-BLK',
            ],
            [
                'name' => 'Nintendo Switch OLED',
                'sku' => 'NINT-SW-OLED',
            ],
            [
                'name' => 'Canon EOS R5',
                'sku' => 'CANR-R5-BODY',
            ],
            [
                'name' => 'AirPods Pro 2nd Gen',
                'sku' => 'APPL-APP-2ND',
            ],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}
