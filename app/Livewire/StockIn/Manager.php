<?php

namespace App\Livewire\StockIn;

use App\Models\Item;
use App\Models\StockIn;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use Livewire\WithPagination;

class Manager extends Component
{
    use WithPagination;

    public ?int $editingId = null;

    public string $item_id = '';

    public string $date = '';

    public string $quantity = '';

    public string $buying_price_per_unit = '';

    public function mount(): void
    {
        $this->date = now()->format('Y-m-d');
    }

    public function render()
    {
        return view('livewire.stock-in.manager', [
            'entries' => StockIn::query()->with('item')->orderByDesc('date')->orderByDesc('id')->paginate(15),
            'items' => Item::query()->orderBy('name')->get(),
        ]);
    }

    public function updatedItemId(): void
    {
        if ($this->item_id === '') {
            return;
        }

        $item = Item::find($this->item_id);

        if ($item) {
            $this->buying_price_per_unit = (string) $item->buying_price;
        }
    }

    public function create(): void
    {
        $this->resetForm();
        $this->dispatch('open-modal', 'stock-in-form');
    }

    public function edit(int $id): void
    {
        $entry = StockIn::findOrFail($id);

        $this->editingId = $entry->id;
        $this->item_id = (string) $entry->item_id;
        $this->date = $entry->date->format('Y-m-d');
        $this->quantity = (string) $entry->quantity;
        $this->buying_price_per_unit = (string) $entry->buying_price_per_unit;

        $this->dispatch('open-modal', 'stock-in-form');
    }

    public function save(): void
    {
        $validated = Validator::make(
            [
                'item_id' => $this->item_id,
                'date' => $this->date,
                'quantity' => $this->quantity,
                'buying_price_per_unit' => $this->buying_price_per_unit,
            ],
            [
                'item_id' => ['required', 'exists:items,id'],
                'date' => ['required', 'date'],
                'quantity' => ['required', 'numeric', 'min:0.001'],
                'buying_price_per_unit' => ['required', 'numeric', 'min:0'],
            ]
        )->validate();

        DB::transaction(function () use ($validated): void {
            $entry = $this->editingId ? StockIn::findOrFail($this->editingId) : new StockIn;
            $itemIds = array_unique(array_filter([$entry->item_id, (int) $validated['item_id']]));
            $items = Item::query()->whereIn('id', $itemIds)->orderBy('id')->lockForUpdate()->get();

            $entry->fill($validated)->save();

            foreach ($items as $item) {
                $this->updateBuyingPrice($item);
            }
        });

        $this->resetForm();
        $this->dispatch('close-modal', 'stock-in-form');
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id): void {
            $entry = StockIn::findOrFail($id);
            $item = Item::query()->lockForUpdate()->findOrFail($entry->item_id);
            $entry->delete();
            $this->updateBuyingPrice($item);
        });
    }

    private function updateBuyingPrice(Item $item): void
    {
        $latest = $item->stockIns()->orderByDesc('date')->orderByDesc('id')->first();

        if ($latest) {
            $item->update(['buying_price' => $latest->buying_price_per_unit]);
        }
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'item_id', 'quantity', 'buying_price_per_unit']);
        $this->date = now()->format('Y-m-d');
        $this->resetErrorBag();
    }
}
