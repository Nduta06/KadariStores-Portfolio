<?php

namespace App\Livewire\StockBalance;

use App\Models\Item;
use App\Support\SalesReport;
use Livewire\Component;

class Overview extends Component
{
    public string $status = 'all';

    public function render()
    {
        $allItems = SalesReport::stockBalances();

        $items = $this->status === 'all'
            ? $allItems
            : $allItems->filter(fn (Item $item) => $item->status === $this->status)->values();

        return view('livewire.stock-balance.overview', [
            'items' => $items,
            'totalStockValue' => $allItems->sum('stock_value'),
            'lowCount' => $allItems->where('status', 'LOW')->count(),
            'outCount' => $allItems->where('status', 'OUT OF STOCK')->count(),
        ]);
    }
}
