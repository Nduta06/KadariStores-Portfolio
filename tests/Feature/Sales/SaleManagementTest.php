<?php

namespace Tests\Feature\Sales;

use App\Livewire\Sales\Manager;
use App\Models\Item;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Tests\TestCase;

class SaleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_view_sales_page(): void
    {
        $this->get('/sales')->assertRedirect('/login');
    }

    public function test_it_can_record_a_sale_by_quantity(): void
    {
        $this->actingAs(User::factory()->create());
        $item = Item::factory()->create(['buying_price' => 160, 'selling_price' => 180]);

        Livewire::test(Manager::class)
            ->set('item_id', (string) $item->id)
            ->set('date', '2026-08-19')
            ->set('mode', 'quantity')
            ->set('value', '0.5')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('sales', [
            'item_id' => $item->id,
            'revenue' => 90,
            'cogs' => 80,
            'profit' => 10,
        ]);
    }

    public function test_it_can_record_a_sale_with_a_selling_price_override(): void
    {
        $this->actingAs(User::factory()->create());
        $item = Item::factory()->create(['buying_price' => 160, 'selling_price' => 180]);

        Livewire::test(Manager::class)
            ->set('item_id', (string) $item->id)
            ->set('date', '2026-08-19')
            ->set('mode', 'quantity')
            ->set('value', '0.5')
            ->set('selling_price_override', '200')
            ->set('selling_price_scope', 'sale')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('sales', [
            'item_id' => $item->id,
            'selling_price_per_unit' => 200,
            'revenue' => 100,
            'cogs' => 80,
            'profit' => 20,
        ]);
    }

    public function test_it_can_record_a_sale_by_amount_paid(): void
    {
        $this->actingAs(User::factory()->create());
        $item = Item::factory()->create(['buying_price' => 100, 'selling_price' => 160]);

        Livewire::test(Manager::class)
            ->set('item_id', (string) $item->id)
            ->set('date', '2026-08-19')
            ->set('mode', 'amount')
            ->set('value', '80')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('sales', [
            'item_id' => $item->id,
            'amount_paid' => 80,
            'revenue' => 80,
            'profit' => 30,
        ]);
    }

    public function test_it_can_record_a_sale_by_pieces_for_an_item_sold_that_way(): void
    {
        $this->actingAs(User::factory()->create());
        $item = Item::factory()->create([
            'buying_price' => 410,
            'selling_price' => 450,
            'eggs_per_tray' => 30,
        ]);

        Livewire::test(Manager::class)
            ->set('item_id', (string) $item->id)
            ->set('date', '2026-08-19')
            ->set('mode', 'pieces')
            ->set('value', '15')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('sales', [
            'item_id' => $item->id,
            'pieces_sold' => 15,
            'quantity' => 0.5,
            'profit' => 20,
        ]);
    }

    public function test_pieces_mode_resets_when_switching_to_an_item_without_eggs_per_tray(): void
    {
        $this->actingAs(User::factory()->create());
        $eggs = Item::factory()->create(['eggs_per_tray' => 30]);
        $maize = Item::factory()->create(['eggs_per_tray' => null]);

        Livewire::test(Manager::class)
            ->set('item_id', (string) $eggs->id)
            ->set('mode', 'pieces')
            ->set('item_id', (string) $maize->id)
            ->assertSet('mode', 'quantity');
    }

    public function test_it_requires_exactly_one_valid_value(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(Manager::class)
            ->set('item_id', '')
            ->set('value', '')
            ->call('save')
            ->assertHasErrors(['item_id', 'value']);
    }

    public function test_it_can_update_an_existing_sale(): void
    {
        $this->actingAs(User::factory()->create());
        $item = Item::factory()->create(['buying_price' => 100, 'selling_price' => 200]);
        $sale = Sale::create(Sale::computeAttributes($item, '2026-08-01', 1, null, null, null));

        Livewire::test(Manager::class)
            ->call('edit', $sale->id)
            ->set('value', '2')
            ->call('save')
            ->assertHasNoErrors();

        $sale->refresh();
        $this->assertEquals(2, $sale->quantity);
        $this->assertEquals(400, $sale->revenue);
    }

    public function test_it_can_delete_a_sale(): void
    {
        $this->actingAs(User::factory()->create());
        $item = Item::factory()->create();
        $sale = Sale::create(Sale::computeAttributes($item, '2026-08-01', 1, null, null, null));

        Livewire::test(Manager::class)->call('delete', $sale->id);

        $this->assertDatabaseMissing('sales', ['id' => $sale->id]);
    }

    public function test_deleting_an_item_with_sales_history_is_blocked(): void
    {
        $item = Item::factory()->create();
        Sale::create(Sale::computeAttributes($item, '2026-08-01', 1, null, null, null));

        $this->expectException(QueryException::class);

        $item->delete();
    }

    public function test_a_different_selling_price_requires_an_explicit_choice_before_saving(): void
    {
        $this->actingAs(User::factory()->create());
        $item = Item::factory()->create(['selling_price' => 180]);
        Livewire::test(Manager::class)->set('item_id', (string) $item->id)
            ->set('value', '1')->set('selling_price_override', '200')
            ->assertSee('This sale only')->assertSee('This sale and future sales')
            ->call('save')->assertHasErrors('selling_price_scope');
        $this->assertDatabaseCount('sales', 0);
        $this->assertEquals(180, $item->fresh()->selling_price);
    }

    public function test_a_one_sale_price_leaves_the_default_unchanged(): void
    {
        $this->actingAs(User::factory()->create());
        $item = Item::factory()->create(['selling_price' => 180]);
        Livewire::test(Manager::class)->set('item_id', (string) $item->id)
            ->set('value', '1')->set('selling_price_override', '200')
            ->set('selling_price_scope', 'sale')->call('save')->assertHasNoErrors();
        $this->assertEquals(180, $item->fresh()->selling_price);
        $this->assertDatabaseHas('sales', ['item_id' => $item->id, 'selling_price_per_unit' => 200]);
        Livewire::test(Manager::class)->set('item_id', (string) $item->id)
            ->set('value', '1')->call('save')->assertHasNoErrors();
        $this->assertEquals(180, Sale::latest('id')->first()->selling_price_per_unit);
    }

    public function test_a_future_price_is_saved_as_default_without_rewriting_past_sales(): void
    {
        $this->actingAs(User::factory()->create());
        $item = Item::factory()->create(['buying_price' => 100, 'selling_price' => 180]);
        $oldSale = Sale::create(Sale::computeAttributes($item, '2026-09-01', 1, null, null, null));
        Livewire::test(Manager::class)->set('item_id', (string) $item->id)
            ->set('value', '1')->set('selling_price_override', '200')
            ->set('selling_price_scope', 'future')->call('save')->assertHasNoErrors()
            ->assertSet('selling_price_scope', '');
        $this->assertEquals(200, $item->fresh()->selling_price);
        $this->assertEquals(180, $oldSale->fresh()->revenue);
        $this->assertEquals(80, $oldSale->fresh()->profit);
        Livewire::test(Manager::class)->set('item_id', (string) $item->id)
            ->set('mode', 'amount')->set('value', '100')->call('save')->assertHasNoErrors();
        $this->assertEquals(0.5, Sale::latest('id')->first()->quantity);
        $this->assertEquals(200, Sale::latest('id')->first()->selling_price_per_unit);
    }

    public function test_changing_item_or_price_resets_the_carry_forward_choice(): void
    {
        $this->actingAs(User::factory()->create());
        $item = Item::factory()->create(['selling_price' => 180]);
        $other = Item::factory()->create(['selling_price' => 220]);
        Livewire::test(Manager::class)->set('item_id', (string) $item->id)
            ->set('selling_price_override', '200')->set('selling_price_scope', 'future')
            ->set('selling_price_override', '210')->assertSet('selling_price_scope', '')
            ->set('selling_price_scope', 'future')->set('item_id', (string) $other->id)
            ->assertSet('selling_price_override', '')->assertSet('selling_price_scope', '');
        $this->assertEquals(180, $item->fresh()->selling_price);
        $this->assertEquals(220, $other->fresh()->selling_price);
    }

    public function test_editing_a_historical_sale_keeps_its_prices_and_does_not_restore_old_defaults(): void
    {
        $this->actingAs(User::factory()->create());
        $item = Item::factory()->create(['buying_price' => 100, 'selling_price' => 180]);
        $sale = Sale::create(Sale::computeAttributes($item, '2026-09-01', 1, null, null, null));
        $item->update(['buying_price' => 150, 'selling_price' => 220]);
        Livewire::test(Manager::class)->call('edit', $sale->id)->set('value', '2')
            ->call('save')->assertHasNoErrors();
        $this->assertEquals(220, $item->fresh()->selling_price);
        $this->assertEquals(180, $sale->fresh()->selling_price_per_unit);
        $this->assertEquals(100, $sale->fresh()->buying_price_per_unit);
        $this->assertEquals(160, $sale->fresh()->profit);
    }

    public function test_sale_and_default_price_changes_roll_back_together(): void
    {
        $this->actingAs(User::factory()->create());
        $item = Item::factory()->create(['selling_price' => 180]);
        Event::listen('eloquent.updating: '.Item::class, function (): void {
            throw new \RuntimeException('Price update failed');
        });
        try {
            Livewire::test(Manager::class)->set('item_id', (string) $item->id)
                ->set('value', '1')->set('selling_price_override', '200')
                ->set('selling_price_scope', 'future')->call('save');
            $this->fail('Expected the price update to fail.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Price update failed', $exception->getMessage());
        }
        $this->assertDatabaseCount('sales', 0);
        $this->assertEquals(180, $item->fresh()->selling_price);
    }
}
