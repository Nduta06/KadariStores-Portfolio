<?php

namespace Tests\Unit\Models;

use App\Models\Item;
use App\Models\Sale;
use App\Models\StockIn;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleComputationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_computes_revenue_cogs_and_profit_from_a_quantity_sale(): void
    {
        // Illustrative arithmetic fixture: half a unit with fixed test prices.
        $item = Item::factory()->create(['buying_price' => 160, 'selling_price' => 180]);

        $attributes = Sale::computeAttributes(
            item: $item,
            date: '2026-08-19',
            quantitySold: 0.5,
            piecesSold: null,
            amountPaid: null,
            buyingPriceOverride: null,
        );

        $this->assertEquals(90, $attributes['revenue']);
        $this->assertEquals(80, $attributes['cogs']);
        $this->assertEquals(10, $attributes['profit']);
    }

    public function test_it_computes_quantity_from_amount_paid(): void
    {
        // Illustrative arithmetic fixture: payment divided by unit price.
        $item = Item::factory()->create(['buying_price' => 100, 'selling_price' => 160]);

        $attributes = Sale::computeAttributes(
            item: $item,
            date: '2026-08-19',
            quantitySold: null,
            piecesSold: null,
            amountPaid: 80,
            buyingPriceOverride: null,
        );

        $this->assertEquals(0.5, $attributes['quantity']);
        $this->assertEquals(80, $attributes['revenue']);
        $this->assertEquals(50, $attributes['cogs']);
        $this->assertEquals(30, $attributes['profit']);
    }

    public function test_it_converts_pieces_sold_into_fractional_units_using_the_items_eggs_per_tray(): void
    {
        $item = Item::factory()->create([
            'unit' => 'Tray',
            'buying_price' => 410,
            'selling_price' => 450,
            'eggs_per_tray' => 30,
        ]);

        $attributes = Sale::computeAttributes(
            item: $item,
            date: '2026-08-19',
            quantitySold: null,
            piecesSold: 15,
            amountPaid: null,
            buyingPriceOverride: null,
        );

        $this->assertEquals(0.5, $attributes['quantity']);
        $this->assertEquals(225, $attributes['revenue']);
        $this->assertEquals(205, $attributes['cogs']);
        $this->assertEquals(20, $attributes['profit']);
    }

    public function test_it_uses_the_buying_price_that_was_in_effect_on_the_sale_date(): void
    {
        $item = Item::factory()->create(['buying_price' => 999, 'selling_price' => 200]);

        StockIn::factory()->create(['item_id' => $item->id, 'date' => '2026-08-01', 'buying_price_per_unit' => 100]);
        StockIn::factory()->create(['item_id' => $item->id, 'date' => '2026-08-15', 'buying_price_per_unit' => 120]);
        StockIn::factory()->create(['item_id' => $item->id, 'date' => '2026-09-01', 'buying_price_per_unit' => 150]);

        // A sale between the second and third delivery should cost at the
        // second delivery's price (120), not the item's current default
        // price and not a delivery that happens later than the sale.
        $attributes = Sale::computeAttributes(
            item: $item,
            date: '2026-08-20',
            quantitySold: 1,
            piecesSold: null,
            amountPaid: null,
            buyingPriceOverride: null,
        );

        $this->assertEquals(120, $attributes['buying_price_per_unit']);
    }

    public function test_it_falls_back_to_the_items_current_buying_price_with_no_delivery_history(): void
    {
        $item = Item::factory()->create(['buying_price' => 140, 'selling_price' => 180]);

        $attributes = Sale::computeAttributes(
            item: $item,
            date: '2026-08-20',
            quantitySold: 1,
            piecesSold: null,
            amountPaid: null,
            buyingPriceOverride: null,
        );

        $this->assertEquals(140, $attributes['buying_price_per_unit']);
    }

    public function test_a_manual_override_wins_over_delivery_history(): void
    {
        $item = Item::factory()->create(['buying_price' => 140, 'selling_price' => 180]);
        StockIn::factory()->create(['item_id' => $item->id, 'date' => '2026-08-01', 'buying_price_per_unit' => 100]);

        $attributes = Sale::computeAttributes(
            item: $item,
            date: '2026-08-20',
            quantitySold: 1,
            piecesSold: null,
            amountPaid: null,
            buyingPriceOverride: 175,
        );

        $this->assertEquals(175, $attributes['buying_price_per_unit']);
    }

    public function test_changing_todays_price_does_not_rewrite_an_older_sales_profit(): void
    {
        $item = Item::factory()->create(['buying_price' => 100, 'selling_price' => 180]);

        $oldSaleAttributes = Sale::computeAttributes(
            item: $item,
            date: '2026-08-01',
            quantitySold: 1,
            piecesSold: null,
            amountPaid: null,
            buyingPriceOverride: null,
        );
        $sale = Sale::create($oldSaleAttributes);

        // The item's price changes today...
        $item->update(['buying_price' => 250]);

        // ...but the already-recorded sale's cost and profit are untouched.
        $sale->refresh();
        $this->assertEquals(100, $sale->buying_price_per_unit);
        $this->assertEquals(80, $sale->profit);
    }
}
