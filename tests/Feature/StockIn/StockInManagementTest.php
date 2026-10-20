<?php

namespace Tests\Feature\StockIn;

use App\Livewire\StockIn\Manager;
use App\Models\Item;
use App\Models\Sale;
use App\Models\StockIn;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Tests\TestCase;

class StockInManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_view_stock_in_page(): void
    {
        $this->get('/stock-in')->assertRedirect('/login');
    }

    public function test_it_auto_fills_the_buying_price_from_the_selected_item(): void
    {
        $this->actingAs(User::factory()->create());
        $item = Item::factory()->create(['buying_price' => 140]);

        Livewire::test(Manager::class)
            ->set('item_id', (string) $item->id)
            ->assertSet('buying_price_per_unit', '140.00');
    }

    public function test_it_can_record_a_delivery_and_computes_total_cost(): void
    {
        $this->actingAs(User::factory()->create());
        $item = Item::factory()->create();

        Livewire::test(Manager::class)
            ->set('item_id', (string) $item->id)
            ->set('date', '2026-08-17')
            ->set('quantity', '10')
            ->set('buying_price_per_unit', '140')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('stock_ins', [
            'item_id' => $item->id,
            'quantity' => 10,
            'buying_price_per_unit' => 140,
            'total_cost' => 1400,
        ]);
    }

    public function test_a_new_delivery_updates_the_default_buying_price(): void
    {
        $this->actingAs(User::factory()->create());
        $item = Item::factory()->create(['buying_price' => 140]);

        Livewire::test(Manager::class)
            ->set('item_id', (string) $item->id)
            ->set('date', '2026-08-17')
            ->set('quantity', '10')
            ->set('buying_price_per_unit', '150')
            ->call('save');

        $this->assertDatabaseHas('stock_ins', [
            'item_id' => $item->id,
            'buying_price_per_unit' => 150,
            'total_cost' => 1500,
        ]);
        $this->assertEquals(150, $item->fresh()->buying_price);
        Livewire::test(Manager::class)->set('item_id', (string) $item->id)
            ->assertSet('buying_price_per_unit', '150.00');
    }

    public function test_it_requires_an_item_date_and_quantity(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(Manager::class)
            ->set('item_id', '')
            ->set('date', '')
            ->set('quantity', '')
            ->set('buying_price_per_unit', '')
            ->call('save')
            ->assertHasErrors(['item_id', 'date', 'quantity', 'buying_price_per_unit']);
    }

    public function test_it_can_update_an_existing_delivery(): void
    {
        $this->actingAs(User::factory()->create());
        $entry = StockIn::factory()->create(['quantity' => 5, 'buying_price_per_unit' => 100]);

        Livewire::test(Manager::class)
            ->call('edit', $entry->id)
            ->set('quantity', '8')
            ->call('save')
            ->assertHasNoErrors();

        $entry->refresh();
        $this->assertEquals(8, $entry->quantity);
        $this->assertEquals(800, $entry->total_cost);
    }

    public function test_it_can_delete_a_delivery(): void
    {
        $this->actingAs(User::factory()->create());
        $entry = StockIn::factory()->create();

        Livewire::test(Manager::class)->call('delete', $entry->id);

        $this->assertDatabaseMissing('stock_ins', ['id' => $entry->id]);
    }

    public function test_deleting_an_item_with_stock_in_history_is_blocked(): void
    {
        $item = Item::factory()->create();
        StockIn::factory()->create(['item_id' => $item->id]);

        $this->expectException(QueryException::class);

        $item->delete();
    }

    public function test_backdated_deliveries_do_not_replace_a_more_recent_buying_price(): void
    {
        $this->actingAs(User::factory()->create());
        $item = Item::factory()->create(['buying_price' => 170]);
        StockIn::factory()->create(['item_id' => $item->id, 'date' => '2026-09-10', 'buying_price_per_unit' => 170]);

        Livewire::test(Manager::class)->set('item_id', (string) $item->id)
            ->set('date', '2026-09-01')->set('quantity', '10')
            ->set('buying_price_per_unit', '120')->call('save')->assertHasNoErrors();

        $this->assertEquals(170, $item->fresh()->buying_price);
    }

    public function test_editing_and_deleting_the_latest_delivery_keeps_the_default_in_sync(): void
    {
        $this->actingAs(User::factory()->create());
        $item = Item::factory()->create(['buying_price' => 150]);
        StockIn::factory()->create(['item_id' => $item->id, 'date' => '2026-09-01', 'buying_price_per_unit' => 120]);
        $latest = StockIn::factory()->create(['item_id' => $item->id, 'date' => '2026-09-10', 'buying_price_per_unit' => 150]);

        Livewire::test(Manager::class)->call('edit', $latest->id)
            ->set('buying_price_per_unit', '160')->call('save')->assertHasNoErrors();
        $this->assertEquals(160, $item->fresh()->buying_price);

        Livewire::test(Manager::class)->call('delete', $latest->id);
        $this->assertEquals(120, $item->fresh()->buying_price);
    }

    public function test_a_new_delivery_does_not_rewrite_past_sale_costs(): void
    {
        $this->actingAs(User::factory()->create());
        $item = Item::factory()->create(['buying_price' => 100, 'selling_price' => 180]);
        $sale = Sale::create(Sale::computeAttributes($item, '2026-09-01', 1, null, null, null));

        Livewire::test(Manager::class)->set('item_id', (string) $item->id)
            ->set('date', '2026-09-10')->set('quantity', '10')
            ->set('buying_price_per_unit', '150')->call('save')->assertHasNoErrors();

        $this->assertEquals(100, $sale->fresh()->cogs);
        $this->assertEquals(80, $sale->fresh()->profit);
        $future = Sale::computeAttributes($item->fresh(), '2026-09-11', 1, null, null, null);
        $this->assertEquals(150, $future['cogs']);
    }

    public function test_delivery_and_default_price_changes_roll_back_together(): void
    {
        $this->actingAs(User::factory()->create());
        $item = Item::factory()->create(['buying_price' => 100]);
        Event::listen('eloquent.updating: '.Item::class, function (): void {
            throw new \RuntimeException('Price update failed');
        });

        try {
            Livewire::test(Manager::class)->set('item_id', (string) $item->id)
                ->set('quantity', '10')->set('buying_price_per_unit', '150')->call('save');
            $this->fail('Expected the price update to fail.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Price update failed', $exception->getMessage());
        }
        $this->assertDatabaseCount('stock_ins', 0);
        $this->assertEquals(100, $item->fresh()->buying_price);
    }
}
