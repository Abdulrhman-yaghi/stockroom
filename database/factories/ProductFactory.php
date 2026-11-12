<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => fake()->words(3, true), 'sku' => fake()->unique()->bothify('SKU-####??'), 'price_cents' => 2500, 'stock' => 20, 'reorder_level' => 5, 'active' => true];
    }
}
