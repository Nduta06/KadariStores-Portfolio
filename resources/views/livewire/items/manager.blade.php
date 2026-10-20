<div>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="w-full sm:max-w-xs">
            <x-text-input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Search items or category..."
                class="w-full"
            />
        </div>

        <x-primary-button wire:click="create" class="justify-center">
            {{ __('Add Item') }}
        </x-primary-button>
    </div>

    @if ($error)
        <div class="mt-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $error }}
        </div>
    @endif

    <div class="mt-6 space-y-3 sm:hidden">
        @forelse ($items as $item)
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="font-semibold text-gray-900">{{ $item->name }}</p>
                        <p class="text-sm text-gray-500">{{ $item->category }} &middot; {{ $item->unit }}</p>
                    </div>
                    <span class="rounded-full bg-brand-50 px-2 py-1 text-xs font-medium text-brand-700">
                        {{ __('Sells KSh :price', ['price' => number_format($item->selling_price, 2)]) }}
                    </span>
                </div>
                <div class="mt-3 flex items-center justify-between text-sm text-gray-500">
                    <span>{{ __('Buys at KSh :price', ['price' => number_format($item->buying_price, 2)]) }}</span>
                    @if ($item->eggs_per_tray)
                        <span>{{ $item->eggs_per_tray }} {{ __('pieces per unit') }}</span>
                    @endif
                </div>
                <div class="mt-3 flex gap-3 text-sm">
                    <button wire:click="edit({{ $item->id }})" class="font-medium text-brand-700 hover:text-brand-800">
                        {{ __('Edit') }}
                    </button>
                    <button
                        wire:click="delete({{ $item->id }})"
                        wire:confirm="{{ __('Delete :name? This cannot be undone.', ['name' => $item->name]) }}"
                        class="font-medium text-red-600 hover:text-red-700"
                    >
                        {{ __('Delete') }}
                    </button>
                </div>
            </div>
        @empty
            <p class="py-8 text-center text-sm text-gray-500">{{ __('No items yet.') }}</p>
        @endforelse
    </div>

    <div class="mt-6 hidden overflow-x-auto rounded-lg border border-gray-200 sm:block">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr class="text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                    <th class="px-4 py-3">{{ __('Name') }}</th>
                    <th class="px-4 py-3">{{ __('Category') }}</th>
                    <th class="px-4 py-3">{{ __('Unit') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('Buying Price') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('Selling Price') }}</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
                @forelse ($items as $item)
                    <tr wire:key="item-row-{{ $item->id }}">
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $item->name }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $item->category }}</td>
                        <td class="px-4 py-3 text-gray-600">
                            {{ $item->unit }}
                            @if ($item->eggs_per_tray)
                                <span class="text-xs text-gray-400">({{ $item->eggs_per_tray }} {{ __('pcs') }})</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right text-gray-600">{{ number_format($item->buying_price, 2) }}</td>
                        <td class="px-4 py-3 text-right text-gray-600">{{ number_format($item->selling_price, 2) }}</td>
                        <td class="px-4 py-3 text-right">
                            <button wire:click="edit({{ $item->id }})" class="font-medium text-brand-700 hover:text-brand-800">
                                {{ __('Edit') }}
                            </button>
                            <button
                                wire:click="delete({{ $item->id }})"
                                wire:confirm="{{ __('Delete :name? This cannot be undone.', ['name' => $item->name]) }}"
                                class="ms-3 font-medium text-red-600 hover:text-red-700"
                            >
                                {{ __('Delete') }}
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">{{ __('No items yet.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <x-modal name="item-form" focusable>
        <form wire:submit="save" class="p-6">
            <h2 class="text-lg font-medium text-gray-900">
                {{ $editingId ? __('Edit Item') : __('Add Item') }}
            </h2>

            <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <x-input-label for="name" :value="__('Item Name')" />
                    <x-text-input id="name" type="text" class="mt-1 block w-full" wire:model="name" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="category" :value="__('Category')" />
                    <x-text-input id="category" type="text" list="category-options" class="mt-1 block w-full" wire:model="category" />
                    <datalist id="category-options">
                        @foreach ($categories as $option)
                            <option value="{{ $option }}"></option>
                        @endforeach
                    </datalist>
                    <x-input-error :messages="$errors->get('category')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="unit" :value="__('Unit')" />
                    <x-text-input id="unit" type="text" list="unit-options" class="mt-1 block w-full" wire:model="unit" placeholder="Kg, L, Tray..." />
                    <datalist id="unit-options">
                        @foreach ($units as $option)
                            <option value="{{ $option }}"></option>
                        @endforeach
                    </datalist>
                    <x-input-error :messages="$errors->get('unit')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="buying_price" :value="__('Buying Price (KSh)')" />
                    <x-text-input id="buying_price" type="number" step="0.01" min="0" class="mt-1 block w-full" wire:model="buying_price" />
                    <x-input-error :messages="$errors->get('buying_price')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="selling_price" :value="__('Selling Price (KSh)')" />
                    <x-text-input id="selling_price" type="number" step="0.01" min="0" class="mt-1 block w-full" wire:model="selling_price" />
                    <x-input-error :messages="$errors->get('selling_price')" class="mt-2" />
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="eggs_per_tray" :value="__('Pieces per unit (optional)')" />
                    <x-text-input id="eggs_per_tray" type="number" min="1" class="mt-1 block w-full" wire:model="eggs_per_tray" placeholder="e.g. 30 eggs per tray" />
                    <p class="mt-1 text-xs text-gray-500">{{ __('Set this only for items you also sell as loose pieces, like eggs sold individually out of a tray.') }}</p>
                    <x-input-error :messages="$errors->get('eggs_per_tray')" class="mt-2" />
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button type="button" x-on:click="$dispatch('close-modal', 'item-form')">
                    {{ __('Cancel') }}
                </x-secondary-button>
                <x-primary-button type="submit">
                    {{ __('Save') }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>
</div>
