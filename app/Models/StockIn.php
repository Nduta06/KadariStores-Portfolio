<?php

namespace App\Models;

use Database\Factories\StockInFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockIn extends Model
{
    /** @use HasFactory<StockInFactory> */
    use HasFactory;

    protected $fillable = [
        'item_id',
        'date',
        'quantity',
        'buying_price_per_unit',
    ];

    protected $casts = [
        'date' => 'date',
        'quantity' => 'decimal:3',
        'buying_price_per_unit' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (StockIn $stockIn) {
            $stockIn->total_cost = round($stockIn->quantity * $stockIn->buying_price_per_unit, 2);
        });
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * The buying price that was in effect for the given item on (or most
     * recently before) the given date. Falls back to the item's current
     * buying price if no delivery has been recorded yet.
     *
     * This is what keeps a sale's cost tied to history: changing today's
     * price never rewrites the profit already recorded on an older sale.
     */
    public static function priceAsOf(Item $item, $date): float
    {
        $stockIn = static::query()
            ->where('item_id', $item->id)
            ->whereDate('date', '<=', $date)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->first();

        return (float) ($stockIn->buying_price_per_unit ?? $item->buying_price);
    }
}
