<div>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-lg border border-gray-200 bg-white p-4">
            <p class="text-sm text-gray-500">{{ __('Current Stock Value') }}</p>
            <p class="mt-1 text-2xl font-semibold text-gray-900">KSh {{ number_format($totalStockValue, 2) }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4">
            <p class="text-sm text-gray-500">{{ __('Items Low on Stock') }}</p>
            <p class="mt-1 text-2xl font-semibold text-accent-600">{{ $lowCount }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4">
            <p class="text-sm text-gray-500">{{ __('Items Out of Stock') }}</p>
            <p class="mt-1 text-2xl font-semibold text-red-600">{{ $outCount }}</p>
        </div>
    </div>

    <div class="mt-6 flex gap-2 text-sm">
        @foreach (['all' => __('All'), 'OK' => __('OK'), 'LOW' => __('Low'), 'OUT OF STOCK' => __('Out of Stock')] as $value => $label)
            <button
                wire:click="$set('status', '{{ $value }}')"
                class="rounded-full px-3 py-1 font-medium {{ $status === $value ? 'bg-brand-700 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}"
            >
                {{ $label }}
            </button>
        @endforeach
    </div>

    <div class="mt-4 space-y-3 sm:hidden">
        @forelse ($items as $item)
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="font-semibold text-gray-900">{{ $item->name }}</p>
                        <p class="text-sm text-gray-500">{{ $item->category }}</p>
                    </div>
                    <span @class([
                        'rounded-full px-2 py-1 text-xs font-medium',
                        'bg-brand-50 text-brand-700' => $item->status === 'OK',
                        'bg-accent-50 text-accent-700' => $item->status === 'LOW',
                        'bg-red-50 text-red-700' => $item->status === 'OUT OF STOCK',
                    ])>
                        {{ $item->status }}
                    </span>
                </div>
                <div class="mt-3 flex items-center justify-between text-sm text-gray-500">
                    <span>{{ __('Remaining: :qty :unit', ['qty' => rtrim(rtrim(number_format($item->remaining, 3), '0'), '.'), 'unit' => $item->unit]) }}</span>
                    <span>{{ __('Value KSh :value', ['value' => number_format($item->stock_value, 2)]) }}</span>
                </div>
            </div>
        @empty
            <p class="py-8 text-center text-sm text-gray-500">{{ __('No items match this filter.') }}</p>
        @endforelse
    </div>

    <div class="mt-4 hidden overflow-x-auto rounded-lg border border-gray-200 sm:block">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr class="text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                    <th class="px-4 py-3">{{ __('Item') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('Stock In') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('Sold') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('Remaining') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('Value') }}</th>
                    <th class="px-4 py-3">{{ __('Status') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
                @forelse ($items as $item)
                    <tr wire:key="balance-row-{{ $item->id }}">
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $item->name }}</td>
                        <td class="px-4 py-3 text-right text-gray-600">{{ $item->total_stock_in ?? 0 }} {{ $item->unit }}</td>
                        <td class="px-4 py-3 text-right text-gray-600">{{ $item->total_sold ?? 0 }} {{ $item->unit }}</td>
                        <td class="px-4 py-3 text-right text-gray-600">{{ $item->remaining }} {{ $item->unit }}</td>
                        <td class="px-4 py-3 text-right text-gray-600">{{ number_format($item->stock_value, 2) }}</td>
                        <td class="px-4 py-3">
                            <span @class([
                                'rounded-full px-2 py-1 text-xs font-medium',
                                'bg-brand-50 text-brand-700' => $item->status === 'OK',
                                'bg-accent-50 text-accent-700' => $item->status === 'LOW',
                                'bg-red-50 text-red-700' => $item->status === 'OUT OF STOCK',
                            ])>
                                {{ $item->status }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">{{ __('No items match this filter.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
