<?php

namespace Tests\Unit\Support;

use App\Models\Item;
use App\Models\Sale;
use App\Models\StockIn;
use App\Support\SalesReport;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class SalesReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_rollup_keeps_separate_days_separate(): void
    {
        $sales = new Collection([
            ['date' => '2026-08-01', 'revenue' => 100, 'cogs' => 60, 'profit' => 40],
            ['date' => '2026-08-02', 'revenue' => 50, 'cogs' => 30, 'profit' => 20],
        ]);

        $trend = SalesReport::buildTrend($sales, 'daily');

        $this->assertCount(2, $trend);
    }

    public function test_monthly_rollup_combines_sales_within_the_same_month(): void
    {
        $sales = new Collection([
            ['date' => '2026-08-01', 'revenue' => 100, 'cogs' => 60, 'profit' => 40],
            ['date' => '2026-08-28', 'revenue' => 100, 'cogs' => 60, 'profit' => 40],
            ['date' => '2026-09-01', 'revenue' => 50, 'cogs' => 30, 'profit' => 20],
        ]);

        $trend = SalesReport::buildTrend($sales, 'monthly');

        $this->assertCount(2, $trend);
        $august = $trend->firstWhere('key', '2026-08');
        $this->assertEquals(200, $august['revenue']);
        $this->assertEquals(80, $august['profit']);
    }

    public function test_yearly_rollup_combines_the_whole_year(): void
    {
        $sales = new Collection([
            ['date' => '2026-01-01', 'revenue' => 100, 'cogs' => 60, 'profit' => 40],
            ['date' => '2026-12-31', 'revenue' => 100, 'cogs' => 60, 'profit' => 40],
            ['date' => '2025-06-15', 'revenue' => 50, 'cogs' => 30, 'profit' => 20],
        ]);

        $trend = SalesReport::buildTrend($sales, 'yearly');

        $this->assertCount(2, $trend);
        $year2026 = $trend->firstWhere('key', '2026');
        $this->assertEquals(200, $year2026['revenue']);
    }

    public function test_trend_is_sorted_most_recent_first(): void
    {
        $sales = new Collection([
            ['date' => '2026-01-01', 'revenue' => 10, 'cogs' => 5, 'profit' => 5],
            ['date' => '2026-03-01', 'revenue' => 10, 'cogs' => 5, 'profit' => 5],
            ['date' => '2026-02-01', 'revenue' => 10, 'cogs' => 5, 'profit' => 5],
        ]);

        $trend = SalesReport::buildTrend($sales, 'monthly');

        $this->assertEquals(['2026-03', '2026-02', '2026-01'], $trend->pluck('key')->all());
    }

    public function test_period_range_for_today_covers_only_today(): void
    {
        $now = Carbon::parse('2026-06-15 14:00:00');

        [$from, $to] = SalesReport::periodRange('today', $now);

        $this->assertEquals('2026-06-15', $from);
        $this->assertEquals('2026-06-15', $to);
    }

    public function test_period_range_for_month_covers_the_whole_month(): void
    {
        $now = Carbon::parse('2026-06-15');

        [$from, $to] = SalesReport::periodRange('month', $now);

        $this->assertEquals('2026-06-01', $from);
        $this->assertEquals('2026-06-30', $to);
    }

    public function test_period_range_for_all_time_has_no_bounds(): void
    {
        [$from, $to] = SalesReport::periodRange('all');

        $this->assertNull($from);
        $this->assertNull($to);
    }

    public function test_stock_balances_compute_remaining_and_status(): void
    {
        $item = Item::factory()->create(['buying_price' => 100]);
        StockIn::factory()->create(['item_id' => $item->id, 'quantity' => 20]);
        Sale::factory()->create(['item_id' => $item->id, 'quantity' => 5]);

        $balances = SalesReport::stockBalances(lowStockThreshold: 5);
        $balance = $balances->firstWhere('id', $item->id);

        $this->assertEquals(15, $balance->remaining);
        $this->assertEquals(1500, $balance->stock_value);
        $this->assertEquals('OK', $balance->status);
    }

    public function test_stock_balances_flag_low_and_out_of_stock(): void
    {
        $low = Item::factory()->create();
        StockIn::factory()->create(['item_id' => $low->id, 'quantity' => 10]);
        Sale::factory()->create(['item_id' => $low->id, 'quantity' => 7]);

        $out = Item::factory()->create();
        StockIn::factory()->create(['item_id' => $out->id, 'quantity' => 5]);
        Sale::factory()->create(['item_id' => $out->id, 'quantity' => 5]);

        $balances = SalesReport::stockBalances(lowStockThreshold: 5);

        $this->assertEquals('LOW', $balances->firstWhere('id', $low->id)->status);
        $this->assertEquals('OUT OF STOCK', $balances->firstWhere('id', $out->id)->status);
    }
}
