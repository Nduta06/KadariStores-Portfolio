<?php

namespace App\Livewire\Sales;

use App\Models\Item;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class Manager extends Component
{
    use WithPagination;

    public ?int $editingId = null;

    public string $item_id = '';

    public string $date = '';

    /** How this sale was recorded: quantity, pieces (loose units), or amount. */
    public string $mode = 'quantity';

    public string $value = '';

    public string $buying_price_override = '';

    public string $selling_price_override = '';

    public string $selling_price_scope = '';

    public function mount(): void
    {
        $this->date = now()->format('Y-m-d');
    }

    public function render()
    {
        $selectedItem = $this->item_id ? Item::find($this->item_id) : null;

        return view('livewire.sales.manager', [
            'sales' => Sale::query()->with('item')->orderByDesc('date')->orderByDesc('id')->paginate(15),
            'items' => Item::query()->orderBy('name')->get(),
            'selectedItem' => $selectedItem,
            'sellingPriceChanged' => $selectedItem && $this->hasDifferentSellingPrice($selectedItem),
        ]);
    }

    public function updatedItemId(): void
    {
        $this->reset(['selling_price_override', 'selling_price_scope', 'buying_price_override']);
        $this->resetValidation(['selling_price_scope', 'selling_price_override']);
        $item = $this->item_id ? Item::find($this->item_id) : null;

        if (! $item || ! $item->sellsByPieces()) {
            if ($this->mode === 'pieces') {
                $this->mode = 'quantity';
            }
        }
    }

    public function updatedSellingPriceOverride(): void
    {
        $this->selling_price_scope = '';
        $this->resetValidation('selling_price_scope');
    }

    private function hasDifferentSellingPrice(Item $item): bool
    {
        return is_numeric($this->selling_price_override)
            && (int) round((float) $this->selling_price_override * 100) !== (int) round((float) $item->selling_price * 100);
    }

    public function create(): void
    {
        $this->resetForm();
        $this->dispatch('open-modal', 'sale-form');
    }

    public function edit(int $id): void
    {
        $sale = Sale::findOrFail($id);

        $this->editingId = $sale->id;
        $this->item_id = (string) $sale->item_id;
        $this->date = $sale->date->format('Y-m-d');
        $this->buying_price_override = $sale->buying_price_override !== null
            ? (string) $sale->buying_price_override
            : '';
        $this->selling_price_override = (string) $sale->selling_price_per_unit;
        $this->selling_price_scope = 'sale';

        if ($sale->quantity_sold !== null) {
            $this->mode = 'quantity';
            $this->value = (string) $sale->quantity_sold;
        } elseif ($sale->pieces_sold !== null) {
            $this->mode = 'pieces';
            $this->value = (string) $sale->pieces_sold;
        } else {
            $this->mode = 'amount';
            $this->value = (string) $sale->amount_paid;
        }

        $this->dispatch('open-modal', 'sale-form');
    }

    public function save(): void
    {
        $validated = Validator::make(
            [
                'item_id' => $this->item_id,
                'date' => $this->date,
                'mode' => $this->mode,
                'value' => $this->value,
                'buying_price_override' => $this->buying_price_override,
                'selling_price_override' => $this->selling_price_override,
                'selling_price_scope' => $this->selling_price_scope,
            ],
            [
                'item_id' => ['required', 'exists:items,id'],
                'date' => ['required', 'date'],
                'mode' => ['required', 'in:quantity,pieces,amount'],
                'value' => ['required', 'numeric', 'min:0.001'],
                'buying_price_override' => ['nullable', 'numeric', 'min:0'],
                'selling_price_override' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
                'selling_price_scope' => ['nullable', 'in:sale,future'],
            ]
        )->validate();

        DB::transaction(function () use ($validated): void {
            $item = Item::query()->lockForUpdate()->findOrFail($validated['item_id']);

            if ($this->hasDifferentSellingPrice($item) && ! in_array($validated['selling_price_scope'], ['sale', 'future'], true)) {
                throw ValidationException::withMessages([
                    'selling_price_scope' => 'Choose whether this selling price is for this sale only or future sales too.',
                ]);
            }

            $existing = $this->editingId ? Sale::findOrFail($this->editingId) : null;
            $buyingPriceOverride = $validated['buying_price_override'] !== '' ? (float) $validated['buying_price_override'] : null;

            if ($existing && $existing->item_id === $item->id
                && $existing->date->format('Y-m-d') === $validated['date']
                && $existing->buying_price_override === null && $buyingPriceOverride === null) {
                $buyingPriceOverride = (float) $existing->buying_price_per_unit;
            }

            $attributes = Sale::computeAttributes(
                item: $item,
                date: $validated['date'],
                quantitySold: $validated['mode'] === 'quantity' ? (float) $validated['value'] : null,
                piecesSold: $validated['mode'] === 'pieces' ? (int) $validated['value'] : null,
                amountPaid: $validated['mode'] === 'amount' ? (float) $validated['value'] : null,
                buyingPriceOverride: $buyingPriceOverride,
                sellingPriceOverride: $validated['selling_price_override'] !== '' ? (float) $validated['selling_price_override'] : null,
            );

            $attributes['buying_price_override'] = $validated['buying_price_override'] !== '' ? (float) $validated['buying_price_override'] : null;
            Sale::updateOrCreate(['id' => $this->editingId], $attributes);

            if ($this->hasDifferentSellingPrice($item) && $validated['selling_price_scope'] === 'future') {
                $item->update(['selling_price' => $validated['selling_price_override']]);
            }
        });

        $this->resetForm();
        $this->dispatch('close-modal', 'sale-form');
    }

    public function delete(int $id): void
    {
        Sale::findOrFail($id)->delete();
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'item_id', 'value', 'buying_price_override', 'selling_price_override', 'selling_price_scope']);
        $this->mode = 'quantity';
        $this->date = now()->format('Y-m-d');
        $this->resetErrorBag();
    }
}
