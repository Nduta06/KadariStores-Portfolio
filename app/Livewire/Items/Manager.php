<?php

namespace App\Livewire\Items;

use App\Models\Item;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Manager extends Component
{
    public ?int $editingId = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|string|max:100')]
    public string $category = '';

    #[Validate('required|string|max:20')]
    public string $unit = '';

    #[Validate('required|numeric|min:0')]
    public string $buying_price = '';

    #[Validate('required|numeric|min:0')]
    public string $selling_price = '';

    #[Validate('nullable|integer|min:1')]
    public ?string $eggs_per_tray = null;

    public string $search = '';

    public ?string $error = null;

    public function render()
    {
        $items = Item::query()
            ->when($this->search, fn ($query) => $query
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('category', 'like', "%{$this->search}%"))
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        return view('livewire.items.manager', [
            'items' => $items,
            'categories' => Item::query()->distinct()->orderBy('category')->pluck('category'),
            'units' => Item::query()->distinct()->orderBy('unit')->pluck('unit'),
        ]);
    }

    public function edit(int $id): void
    {
        $item = Item::findOrFail($id);

        $this->editingId = $item->id;
        $this->name = $item->name;
        $this->category = $item->category;
        $this->unit = $item->unit;
        $this->buying_price = (string) $item->buying_price;
        $this->selling_price = (string) $item->selling_price;
        $this->eggs_per_tray = $item->eggs_per_tray ? (string) $item->eggs_per_tray : null;
        $this->error = null;

        $this->dispatch('open-modal', 'item-form');
    }

    public function create(): void
    {
        $this->resetForm();
        $this->dispatch('open-modal', 'item-form');
    }

    public function save(): void
    {
        $validated = Validator::make(
            [
                'name' => $this->name,
                'category' => $this->category,
                'unit' => $this->unit,
                'buying_price' => $this->buying_price,
                'selling_price' => $this->selling_price,
                'eggs_per_tray' => $this->eggs_per_tray,
            ],
            [
                'name' => ['required', 'string', 'max:255', 'unique:items,name,'.$this->editingId],
                'category' => ['required', 'string', 'max:100'],
                'unit' => ['required', 'string', 'max:20'],
                'buying_price' => ['required', 'numeric', 'min:0'],
                'selling_price' => ['required', 'numeric', 'min:0'],
                'eggs_per_tray' => ['nullable', 'integer', 'min:1'],
            ]
        )->validate();

        Item::updateOrCreate(['id' => $this->editingId], $validated);

        $this->resetForm();
        $this->dispatch('close-modal', 'item-form');
    }

    public function delete(int $id): void
    {
        try {
            Item::findOrFail($id)->delete();
        } catch (QueryException) {
            $this->error = 'This item has recorded stock or sales history and cannot be deleted.';
        }
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'category', 'unit', 'buying_price', 'selling_price', 'eggs_per_tray']);
        $this->resetErrorBag();
        $this->error = null;
    }
}
