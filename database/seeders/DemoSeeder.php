<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\Sale;
use App\Models\StockIn;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Demo seeding is restricted to local and testing environments.');
        }
        if (User::exists() || Item::exists() || StockIn::exists() || Sale::exists()) {
            throw new RuntimeException('Demo seeding requires an empty, separate demo database. Existing records were not changed.');
        }

        DB::transaction(function (): void {
            User::create([
                'name' => 'Demo Reviewer',
                'email' => 'reviewer@example.test',
                'email_verified_at' => now(),
                'password' => Hash::make('PortfolioDemo2026!'),
            ]);
            $this->call(ItemSeeder::class);
            foreach (Item::orderBy('id')->get() as $index => $item) {
                $initialPrice = (float) $item->buying_price;
                $initialQuantity = $index === 6 ? 1 : ($index === 7 ? 4 : 60);
                StockIn::create([
                    'item_id' => $item->id, 'date' => today()->subDays(40),
                    'quantity' => $initialQuantity, 'buying_price_per_unit' => $initialPrice,
                ]);
                if ($index < 6) {
                    StockIn::create([
                        'item_id' => $item->id, 'date' => today()->subDays(12),
                        'quantity' => 40, 'buying_price_per_unit' => $initialPrice + 7,
                    ]);
                    $item->update(['buying_price' => $initialPrice + 7]);
                }
                if ($index >= 6) {
                    $quantity = $index === 6 ? 1 : 0.5;
                    Sale::create(Sale::computeAttributes($item, today()->toDateString(), $quantity, null, null, null));

                    continue;
                }
                for ($day = 30; $day >= 0; $day--) {
                    $quantity = [0.25, 0.5, 1, 1.5][($day + $index) % 4];
                    Sale::create(Sale::computeAttributes($item, today()->subDays($day)->toDateString(), $quantity, null, null, null));
                }
            }
        });
    }
}
