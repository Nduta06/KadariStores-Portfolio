<?php

namespace Database\Seeders;

use App\Models\Item;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    /** Fictional sample catalogue; prices are illustrative, not market quotes. */
    public function run(): void
    {
        foreach ([
            ['name' => 'Demo Rice', 'category' => 'Cereal', 'unit' => 'Kg', 'buying_price' => 111, 'selling_price' => 159],
            ['name' => 'Demo Beans', 'category' => 'Cereal', 'unit' => 'Kg', 'buying_price' => 127, 'selling_price' => 179],
            ['name' => 'Demo Lentils', 'category' => 'Cereal', 'unit' => 'Kg', 'buying_price' => 143, 'selling_price' => 199],
            ['name' => 'Demo Maize', 'category' => 'Cereal', 'unit' => 'Kg', 'buying_price' => 47, 'selling_price' => 83],
            ['name' => 'Demo Flour', 'category' => 'Unga', 'unit' => 'Kg', 'buying_price' => 63, 'selling_price' => 97],
            ['name' => 'Demo Cooking Oil', 'category' => 'Oil', 'unit' => 'L', 'buying_price' => 213, 'selling_price' => 267],
            ['name' => 'Demo Eggs', 'category' => 'Eggs', 'unit' => 'Tray', 'buying_price' => 317, 'selling_price' => 389, 'eggs_per_tray' => 30],
            ['name' => 'Demo Honey', 'category' => 'Honey', 'unit' => 'Kg', 'buying_price' => 337, 'selling_price' => 479],
        ] as $attributes) {
            Item::create($attributes);
        }
    }
}
