<?php

namespace App\Support;

use App\Models\Item;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Pure reporting helpers shared by the Dashboard and Stock Balance pages.
 * Kept outside the Livewire components so the aggregation logic can be
 * unit tested directly, without going through Livewire's test helpers.
 */
class SalesReport
{
    /**
     * @return array{0: ?string, 1: ?string}
     */
    public static function periodRange(string $period, ?Carbon $now = null): array
    {
        $now ??= Carbon::now();

        return match ($period) {
            'today' => [$now->copy()->startOfDay()->format('Y-m-d'), $now->copy()->endOfDay()->format('Y-m-d')],
            'month' => [$now->copy()->startOfMonth()->format('Y-m-d'), $now->copy()->endOfMonth()->format('Y-m-d')],
            'year' => [$now->copy()->startOfYear()->format('Y-m-d'), $now->copy()->endOfYear()->format('Y-m-d')],
            default => [null, null],
        };
    }

    /**
     * Group a collection of sales (each with a 'date', 'revenue', 'cogs',
     * 'profit') into daily, monthly, or yearly buckets, most recent first.
     */
    public static function buildTrend(Collection $sales, string $rollup, int $limit = 12): Collection
    {
        $key = match ($rollup) {
            'monthly' => fn (Carbon $date) => $date->format('Y-m'),
            'yearly' => fn (Carbon $date) => $date->format('Y'),
            default => fn (Carbon $date) => $date->format('Y-m-d'),
        };

        $label = match ($rollup) {
            'monthly' => fn (Carbon $date) => $date->format('M Y'),
            'yearly' => fn (Carbon $date) => $date->format('Y'),
            default => fn (Carbon $date) => $date->format('d M Y'),
        };

        return $sales
            ->groupBy(fn ($sale) => $key(Carbon::parse($sale['date'])))
            ->map(function (Collection $rows, string $groupKey) use ($label) {
                $date = Carbon::parse($rows->first()['date']);

                return [
                    'key' => $groupKey,
                    'label' => $label($date),
                    'revenue' => $rows->sum('revenue'),
                    'cogs' => $rows->sum('cogs'),
                    'profit' => $rows->sum('profit'),
                ];
            })
            ->sortByDesc('key')
            ->take($limit)
            ->values();
    }

    /**
     * Every item with its computed stock balance: quantity in, quantity
     * sold, remaining, value at current buying price, and OK/LOW/OUT OF
     * STOCK status.
     */
    public static function stockBalances(?float $lowStockThreshold = null): Collection
    {
        $threshold = $lowStockThreshold ?? config('inventory.low_stock_threshold');

        return Item::query()
            ->withSum('stockIns as total_stock_in', 'quantity')
            ->withSum('sales as total_sold', 'quantity')
            ->orderBy('name')
            ->get()
            ->map(function (Item $item) use ($threshold) {
                $stockIn = (float) ($item->total_stock_in ?? 0);
                $sold = (float) ($item->total_sold ?? 0);
                $remaining = $stockIn - $sold;

                $item->remaining = $remaining;
                $item->stock_value = $remaining * (float) $item->buying_price;
                $item->status = match (true) {
                    $remaining <= 0 => 'OUT OF STOCK',
                    $remaining <= $threshold => 'LOW',
                    default => 'OK',
                };

                return $item;
            });
    }
}
