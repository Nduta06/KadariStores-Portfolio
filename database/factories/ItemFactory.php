<?php

namespace Database\Factories;

use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Item>
 */
class ItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $buyingPrice = $this->faker->randomFloat(2, 50, 300);

        return [
            'name' => $this->faker->unique()->words(2, true),
            'category' => $this->faker->randomElement(['Cereal', 'Uji', 'Eggs', 'Honey', 'Milk', 'Oil', 'Sugar']),
            'unit' => $this->faker->randomElement(['Kg', 'L', 'Tray']),
            'buying_price' => $buyingPrice,
            'selling_price' => $buyingPrice + $this->faker->randomFloat(2, 20, 80),
            'eggs_per_tray' => null,
        ];
    }
}
