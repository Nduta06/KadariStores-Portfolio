<?php

namespace App\Models;

use Database\Factories\SaleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sale extends Model
{
    /** @use HasFactory<SaleFactory> */
    use HasFactory;

    protected $fillable = [
        'item_id',
        'date',
        'quantity_sold',
        'pieces_sold',
        'amount_paid',
        'buying_price_override',
        'selling_price_per_unit',
        'buying_price_per_unit',
        'quantity',
        'revenue',
        'cogs',
        'profit',
    ];

    protected $casts = [
        'date' => 'date',
        'quantity_sold' => 'decimal:3',
        'pieces_sold' => 'integer',
        'amount_paid' => 'decimal:2',
        'buying_price_override' => 'decimal:2',
        'selling_price_per_unit' => 'decimal:2',
        'buying_price_per_unit' => 'decimal:2',
        'quantity' => 'decimal:3',
        'revenue' => 'decimal:2',
        'cogs' => 'decimal:2',
        'profit' => 'decimal:2',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Build the computed fields for a sale from the shop-owner's raw input.
     *
     * Exactly one of $quantitySold, $piecesSold or $amountPaid should be
     * given (one input mode per sale).
     * Prices are captured when the sale is recorded. A selling-price
     * override reflects a price agreed for that sale, while the buying
     * price uses history (or its manual override).
     */
    public static function computeAttributes(
        Item $item,
        string $date,
        ?float $quantitySold,
        ?int $piecesSold,
        ?float $amountPaid,
        ?float $buyingPriceOverride,
        ?float $sellingPriceOverride = null,
    ): array {
        $sellingPricePerUnit = $sellingPriceOverride ?? (float) $item->selling_price;
        $buyingPricePerUnit = $buyingPriceOverride ?? StockIn::priceAsOf($item, $date);

        if ($quantitySold !== null || $piecesSold !== null) {
            $eggsPerTray = $item->eggs_per_tray ?: 1;
            $quantity = ($quantitySold ?? 0) + ($piecesSold ?? 0) / $eggsPerTray;
            $revenue = $quantity * $sellingPricePerUnit;
        } else {
            $quantity = $sellingPricePerUnit > 0 ? $amountPaid / $sellingPricePerUnit : 0;
            $revenue = $amountPaid;
        }

        $cogs = $quantity * $buyingPricePerUnit;

        return [
            'item_id' => $item->id,
            'date' => $date,
            'quantity_sold' => $quantitySold,
            'pieces_sold' => $piecesSold,
            'amount_paid' => $amountPaid,
            'buying_price_override' => $buyingPriceOverride,
            'selling_price_per_unit' => round($sellingPricePerUnit, 2),
            'buying_price_per_unit' => round($buyingPricePerUnit, 2),
            'quantity' => round($quantity, 3),
            'revenue' => round($revenue, 2),
            'cogs' => round($cogs, 2),
            'profit' => round($revenue - $cogs, 2),
        ];
    }
}
