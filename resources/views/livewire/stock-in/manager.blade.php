<div>
    <div class="flex items-center justify-between">
        <p class="text-sm text-gray-500">{{ __('Record every delivery so stock levels and costs stay accurate.') }}</p>
        <x-primary-button wire:click="create" class="justify-center whitespace-nowrap">
            {{ __('Record Delivery') }}
        </x-primary-button>
    </div>

    <div class="mt-6 space-y-3 sm:hidden">
        @forelse ($entries as $entry)
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="font-semibold text-gray-900">{{ $entry->item->name }}</p>
                        <p class="text-sm text-gray-500">{{ $entry->date->format('d M Y') }}</p>
                    </div>
                    <span class="rounded-full bg-brand-50 px-2 py-1 text-xs font-medium text-brand-700">
                        {{ __('KSh :total', ['total' => number_format($entry->total_cost, 2)]) }}
                    </span>
                </div>
                <div class="mt-3 text-sm text-gray-500">
                    {{ $entry->quantity }} {{ $entry->item->unit }} &times; KSh {{ number_format($entry->buying_price_per_unit, 2) }}
                </div>
                <div class="mt-3 flex gap-3 text-sm">
                    <button wire:click="edit({{ $entry->id }})" class="font-medium text-brand-700 hover:text-brand-800">
                        {{ __('Edit') }}
                    </button>
                    <button
                        wire:click="delete({{ $entry->id }})"
                        wire:confirm="{{ __('Delete this delivery record?') }}"
                        class="font-medium text-red-600 hover:text-red-700"
                    >
                        {{ __('Delete') }}
                    </button>
                </div>
            </div>
        @empty
            <p class="py-8 text-center text-sm text-gray-500">{{ __('No deliveries recorded yet.') }}</p>
        @endforelse
    </div>

    <div class="mt-6 hidden overflow-x-auto rounded-lg border border-gray-200 sm:block">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr class="text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                    <th class="px-4 py-3">{{ __('Date') }}</th>
                    <th class="px-4 py-3">{{ __('Item') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('Quantity') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('Buying Price') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('Total Cost') }}</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
                @forelse ($entries as $entry)
                    <tr wire:key="stock-in-row-{{ $entry->id }}">
                        <td class="px-4 py-3 text-gray-600">{{ $entry->date->format('d M Y') }}</td>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $entry->item->name }}</td>
                        <td class="px-4 py-3 text-right text-gray-600">{{ $entry->quantity }} {{ $entry->item->unit }}</td>
                        <td class="px-4 py-3 text-right text-gray-600">{{ number_format($entry->buying_price_per_unit, 2) }}</td>
                        <td class="px-4 py-3 text-right font-medium text-gray-900">{{ number_format($entry->total_cost, 2) }}</td>
                        <td class="px-4 py-3 text-right">
                            <button wire:click="edit({{ $entry->id }})" class="font-medium text-brand-700 hover:text-brand-800">
                                {{ __('Edit') }}
                            </button>
                            <button
                                wire:click="delete({{ $entry->id }})"
                                wire:confirm="{{ __('Delete this delivery record?') }}"
                                class="ms-3 font-medium text-red-600 hover:text-red-700"
                            >
                                {{ __('Delete') }}
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">{{ __('No deliveries recorded yet.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $entries->links() }}
    </div>

    <x-modal name="stock-in-form" focusable>
        <form wire:submit="save" class="p-6">
            <h2 class="text-lg font-medium text-gray-900">
                {{ $editingId ? __('Edit Delivery') : __('Record Delivery') }}
            </h2>

            <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <x-input-label for="item_id" :value="__('Item')" />
                    <select id="item_id" wire:model.live="item_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        <option value="">{{ __('Select item...') }}</option>
                        @foreach ($items as $option)
                            <option value="{{ $option->id }}">{{ $option->name }} ({{ $option->unit }})</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('item_id')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="date" :value="__('Date')" />
                    <x-text-input id="date" type="date" class="mt-1 block w-full" wire:model="date" />
                    <x-input-error :messages="$errors->get('date')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="quantity" :value="__('Quantity Added')" />
                    <x-text-input id="quantity" type="number" step="0.001" min="0" class="mt-1 block w-full" wire:model="quantity" />
                    <x-input-error :messages="$errors->get('quantity')" class="mt-2" />
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="buying_price_per_unit" :value="__('Buying Price Per Unit (KSh)')" />
                    <x-text-input id="buying_price_per_unit" type="number" step="0.01" min="0" class="mt-1 block w-full" wire:model="buying_price_per_unit" />
                    <p class="mt-1 text-xs text-gray-500">{{ __('Enter the price paid for this delivery. The latest dated delivery sets the default buying price for future deliveries. Past sales keep their recorded costs.') }}</p>
                    <x-input-error :messages="$errors->get('buying_price_per_unit')" class="mt-2" />
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button type="button" x-on:click="$dispatch('close-modal', 'stock-in-form')">
                    {{ __('Cancel') }}
                </x-secondary-button>
                <x-primary-button type="submit">
                    {{ __('Save') }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>
</div>
