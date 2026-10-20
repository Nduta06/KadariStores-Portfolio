<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\StockIn;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockIn>
 */
class StockInFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'item_id' => Item::factory(),
            'date' => $this->faker->dateTimeBetween('-2 months', 'now')->format('Y-m-d'),
            'quantity' => $this->faker->randomFloat(3, 1, 50),
            'buying_price_per_unit' => $this->faker->randomFloat(2, 50, 300),
        ];
    }
}
