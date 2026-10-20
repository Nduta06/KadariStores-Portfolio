<?php

namespace App\Models;

use Database\Factories\ItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Item extends Model
{
    /** @use HasFactory<ItemFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'unit',
        'buying_price',
        'selling_price',
        'eggs_per_tray',
    ];

    protected $casts = [
        'buying_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'eggs_per_tray' => 'integer',
    ];

    public function stockIns(): HasMany
    {
        return $this->hasMany(StockIn::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Units sold as loose pieces (e.g. eggs) convert to whole units via eggs_per_tray.
     */
    public function sellsByPieces(): bool
    {
        return ! is_null($this->eggs_per_tray);
    }
}
