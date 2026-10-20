<?php

namespace Tests\Feature\StockBalance;

use App\Livewire\StockBalance\Overview;
use App\Models\Item;
use App\Models\Sale;
use App\Models\StockIn;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StockBalanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_view_stock_balance_page(): void
    {
        $this->get('/stock-balance')->assertRedirect('/login');
    }

    public function test_it_computes_remaining_stock_and_value(): void
    {
        $this->actingAs(User::factory()->create());
        $item = Item::factory()->create(['buying_price' => 100]);
        StockIn::factory()->create(['item_id' => $item->id, 'quantity' => 20]);
        Sale::factory()->create(['item_id' => $item->id, 'quantity' => 5]);

        Livewire::test(Overview::class)
            ->assertSee($item->name)
            ->assertSee('1,500.00'); // (20 - 5) * 100
    }

    public function test_an_item_with_no_stock_is_flagged_out_of_stock(): void
    {
        $this->actingAs(User::factory()->create());
        $item = Item::factory()->create();
        StockIn::factory()->create(['item_id' => $item->id, 'quantity' => 5]);
        Sale::factory()->create(['item_id' => $item->id, 'quantity' => 5]);

        Livewire::test(Overview::class)->assertSee('OUT OF STOCK');
    }

    public function test_an_item_below_the_low_stock_threshold_is_flagged_low(): void
    {
        config(['inventory.low_stock_threshold' => 5]);
        $this->actingAs(User::factory()->create());
        $item = Item::factory()->create();
        StockIn::factory()->create(['item_id' => $item->id, 'quantity' => 10]);
        Sale::factory()->create(['item_id' => $item->id, 'quantity' => 7]);

        Livewire::test(Overview::class)->assertSee('LOW');
    }

    public function test_it_can_filter_by_status(): void
    {
        $this->actingAs(User::factory()->create());
        $healthy = Item::factory()->create(['name' => 'Healthy Item']);
        StockIn::factory()->create(['item_id' => $healthy->id, 'quantity' => 100]);

        $outOfStock = Item::factory()->create(['name' => 'Depleted Item']);
        StockIn::factory()->create(['item_id' => $outOfStock->id, 'quantity' => 5]);
        Sale::factory()->create(['item_id' => $outOfStock->id, 'quantity' => 5]);

        Livewire::test(Overview::class)
            ->set('status', 'OUT OF STOCK')
            ->assertSee('Depleted Item')
            ->assertDontSee('Healthy Item');
    }
}
