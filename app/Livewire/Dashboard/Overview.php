<?php

namespace App\Livewire\Dashboard;

use App\Models\Item;
use App\Models\Sale;
use App\Models\StockIn;
use App\Support\SalesReport;
use Illuminate\Support\Collection;
use Livewire\Component;

class Overview extends Component
{
    /** Which window the summary cards cover: today, month, year, or all. */
    public string $period = 'month';

    /** How the trend table below is bucketed: daily, monthly, or yearly. */
    public string $rollup = 'daily';

    public float $revenue = 0;

    public float $cogs = 0;

    public float $profit = 0;

    public float $margin = 0;

    public function render()
    {
        [$from, $to] = SalesReport::periodRange($this->period);

        $sales = Sale::query()
            ->when($from, fn ($query) => $query->whereDate('date', '>=', $from)->whereDate('date', '<=', $to))
            ->get(['date', 'item_id', 'revenue', 'cogs', 'profit']);

        $this->revenue = (float) $sales->sum('revenue');
        $this->cogs = (float) $sales->sum('cogs');
        $this->profit = $this->revenue - $this->cogs;
        $this->margin = $this->revenue > 0 ? $this->profit / $this->revenue : 0;

        $balances = SalesReport::stockBalances();

        $allSales = Sale::query()->get(['date', 'item_id', 'revenue', 'cogs', 'profit']);

        $byItem = $allSales->groupBy('item_id')
            ->map(fn (Collection $rows) => [
                'revenue' => $rows->sum('revenue'),
                'profit' => $rows->sum('profit'),
            ]);

        $itemBreakdown = Item::query()
            ->get(['id', 'name', 'category'])
            ->map(function (Item $item) use ($byItem) {
                $item->revenue = $byItem[$item->id]['revenue'] ?? 0;
                $item->profit = $byItem[$item->id]['profit'] ?? 0;

                return $item;
            })
            ->filter(fn (Item $item) => $item->revenue > 0)
            ->sortByDesc('revenue')
            ->values();

        $categoryBreakdown = $balances->groupBy('category')
            ->map(fn (Collection $rows) => $rows->sum('stock_value'))
            ->sortByDesc(fn ($value) => $value);

        return view('livewire.dashboard.overview', [
            'stockBoughtToDate' => StockIn::query()->sum('total_cost'),
            'currentStockValue' => $balances->sum('stock_value'),
            'lowStockCount' => $balances->where('status', 'LOW')->count(),
            'outOfStockCount' => $balances->where('status', 'OUT OF STOCK')->count(),
            'trend' => SalesReport::buildTrend($allSales, $this->rollup),
            'itemBreakdown' => $itemBreakdown,
            'categoryBreakdown' => $categoryBreakdown,
        ]);
    }
}
