<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = $this->faker->randomFloat(3, 1, 20);
        $sellingPrice = $this->faker->randomFloat(2, 100, 300);
        $buyingPrice = $sellingPrice - $this->faker->randomFloat(2, 20, 60);
        $revenue = $quantity * $sellingPrice;
        $cogs = $quantity * $buyingPrice;

        return [
            'item_id' => Item::factory(),
            'date' => $this->faker->dateTimeBetween('-2 months', 'now')->format('Y-m-d'),
            'quantity_sold' => $quantity,
            'pieces_sold' => null,
            'amount_paid' => null,
            'buying_price_override' => null,
            'selling_price_per_unit' => $sellingPrice,
            'buying_price_per_unit' => $buyingPrice,
            'quantity' => $quantity,
            'revenue' => round($revenue, 2),
            'cogs' => round($cogs, 2),
            'profit' => round($revenue - $cogs, 2),
        ];
    }
}
