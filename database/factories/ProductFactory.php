<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = $this->faker->randomFloat(8, 0, 1000);
        $wac = $this->faker->randomFloat(4, 10, 500);
        
        return [
            'name' => $this->faker->words(3, true),
            'sku' => strtoupper($this->faker->unique()->bothify('??###??')),
            'current_wac' => $wac,
            'current_quantity' => $quantity,
            'total_cost' => $wac * $quantity,
        ];
    }
}
