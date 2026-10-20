<div>
    <div class="flex items-center justify-between">
        <p class="text-sm text-gray-500">{{ __('Record every sale to track revenue and profit.') }}</p>
        <x-primary-button wire:click="create" class="justify-center whitespace-nowrap">
            {{ __('Record Sale') }}
        </x-primary-button>
    </div>

    <div class="mt-6 space-y-3 sm:hidden">
        @forelse ($sales as $sale)
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="font-semibold text-gray-900">{{ $sale->item->name }}</p>
                        <p class="text-sm text-gray-500">{{ $sale->date->format('d M Y') }}</p>
                    </div>
                    <span class="rounded-full px-2 py-1 text-xs font-medium {{ $sale->profit >= 0 ? 'bg-brand-50 text-brand-700' : 'bg-red-50 text-red-700' }}">
                        {{ __('Profit KSh :profit', ['profit' => number_format($sale->profit, 2)]) }}
                    </span>
                </div>
                <div class="mt-3 flex items-center justify-between text-sm text-gray-500">
                    <span>{{ __('Revenue KSh :revenue', ['revenue' => number_format($sale->revenue, 2)]) }}</span>
                    <span>{{ __('Cost KSh :cogs', ['cogs' => number_format($sale->cogs, 2)]) }}</span>
                </div>
                <div class="mt-3 flex gap-3 text-sm">
                    <button wire:click="edit({{ $sale->id }})" class="font-medium text-brand-700 hover:text-brand-800">
                        {{ __('Edit') }}
                    </button>
                    <button
                        wire:click="delete({{ $sale->id }})"
                        wire:confirm="{{ __('Delete this sale?') }}"
                        class="font-medium text-red-600 hover:text-red-700"
                    >
                        {{ __('Delete') }}
                    </button>
                </div>
            </div>
        @empty
            <p class="py-8 text-center text-sm text-gray-500">{{ __('No sales recorded yet.') }}</p>
        @endforelse
    </div>

    <div class="mt-6 hidden overflow-x-auto rounded-lg border border-gray-200 sm:block">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr class="text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                    <th class="px-4 py-3">{{ __('Date') }}</th>
                    <th class="px-4 py-3">{{ __('Item') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('Quantity') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('Revenue') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('Cost') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('Profit') }}</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
                @forelse ($sales as $sale)
                    <tr wire:key="sale-row-{{ $sale->id }}">
                        <td class="px-4 py-3 text-gray-600">{{ $sale->date->format('d M Y') }}</td>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $sale->item->name }}</td>
                        <td class="px-4 py-3 text-right text-gray-600">{{ $sale->quantity }} {{ $sale->item->unit }}</td>
                        <td class="px-4 py-3 text-right text-gray-600">{{ number_format($sale->revenue, 2) }}</td>
                        <td class="px-4 py-3 text-right text-gray-600">{{ number_format($sale->cogs, 2) }}</td>
                        <td class="px-4 py-3 text-right font-medium {{ $sale->profit >= 0 ? 'text-brand-700' : 'text-red-600' }}">
                            {{ number_format($sale->profit, 2) }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button wire:click="edit({{ $sale->id }})" class="font-medium text-brand-700 hover:text-brand-800">
                                {{ __('Edit') }}
                            </button>
                            <button
                                wire:click="delete({{ $sale->id }})"
                                wire:confirm="{{ __('Delete this sale?') }}"
                                class="ms-3 font-medium text-red-600 hover:text-red-700"
                            >
                                {{ __('Delete') }}
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500">{{ __('No sales recorded yet.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $sales->links() }}
    </div>

    <x-modal name="sale-form" focusable>
        <form wire:submit="save" class="p-6">
            <h2 class="text-lg font-medium text-gray-900">
                {{ $editingId ? __('Edit Sale') : __('Record Sale') }}
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

                <div class="sm:col-span-2">
                    <x-input-label :value="__('Recorded as')" />
                    <div class="mt-1 flex flex-wrap gap-4 text-sm text-gray-700">
                        <label class="inline-flex items-center gap-2">
                            <input type="radio" wire:model.live="mode" value="quantity" class="border-gray-300 text-brand-700 focus:ring-brand-500">
                            {{ __('Quantity sold') }}
                        </label>
                        @if ($selectedItem && $selectedItem->sellsByPieces())
                            <label class="inline-flex items-center gap-2">
                                <input type="radio" wire:model.live="mode" value="pieces" class="border-gray-300 text-brand-700 focus:ring-brand-500">
                                {{ __('Pieces sold') }}
                            </label>
                        @endif
                        <label class="inline-flex items-center gap-2">
                            <input type="radio" wire:model.live="mode" value="amount" class="border-gray-300 text-brand-700 focus:ring-brand-500">
                            {{ __('Amount paid') }}
                        </label>
                    </div>
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="value" :value="
                        $mode === 'quantity' ? __('Quantity Sold') . ($selectedItem ? ' ('.$selectedItem->unit.')' : '')
                        : ($mode === 'pieces' ? __('Pieces Sold') : __('Amount Paid (KSh)'))
                    " />
                    <x-text-input id="value" type="number" step="0.001" min="0" class="mt-1 block w-full" wire:model="value" />
                    <x-input-error :messages="$errors->get('value')" class="mt-2" />
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="buying_price_override" :value="__('Buying Price (if different)')" />
                    <x-text-input id="buying_price_override" type="number" step="0.01" min="0" class="mt-1 block w-full" wire:model="buying_price_override" placeholder="{{ __('Leave blank to use the delivery price') }}" />
                    <x-input-error :messages="$errors->get('buying_price_override')" class="mt-2" />
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="selling_price_override" :value="__('Selling Price (if different)')" />
                    <x-text-input id="selling_price_override" type="number" step="0.01" min="0" class="mt-1 block w-full" wire:model.live.debounce.300ms="selling_price_override" placeholder="{{ __('Leave blank to use the item price') }}" />
                    <x-input-error :messages="$errors->get('selling_price_override')" class="mt-2" />
                    @if ($selectedItem)
                        <p class="mt-1 text-xs text-gray-500">{{ __('Current default: KSh :price per :unit', ['price' => number_format($selectedItem->selling_price, 2), 'unit' => $selectedItem->unit]) }}</p>
                    @endif
                    @if ($sellingPriceChanged)
                        <fieldset class="mt-3 rounded-lg border border-brand-200 bg-brand-50 p-3">
                            <legend class="px-1 text-sm font-medium text-gray-900">{{ __('Use this new selling price for:') }}</legend>
                            <div class="space-y-2 text-sm text-gray-700">
                                <label class="flex items-center gap-2">
                                    <input type="radio" wire:model="selling_price_scope" value="sale" class="border-gray-300 text-brand-700 focus:ring-brand-500">
                                    {{ __('This sale only') }}
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="radio" wire:model="selling_price_scope" value="future" class="border-gray-300 text-brand-700 focus:ring-brand-500">
                                    {{ __('This sale and future sales') }}
                                </label>
                            </div>
                            <p class="mt-2 text-xs text-gray-600">{{ __('Past sales keep their original prices. The default changes only when you save.') }}</p>
                        </fieldset>
                    @endif
                    <x-input-error :messages="$errors->get('selling_price_scope')" class="mt-2" />
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button type="button" x-on:click="$dispatch('close-modal', 'sale-form')">
                    {{ __('Cancel') }}
                </x-secondary-button>
                <x-primary-button type="submit">
                    {{ __('Save') }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>
</div>
