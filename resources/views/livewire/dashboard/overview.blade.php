<div>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h3 class="text-sm font-medium text-gray-500">{{ __('Showing') }}</h3>
        <div class="flex gap-2 text-sm">
            @foreach (['today' => __('Today'), 'month' => __('This Month'), 'year' => __('This Year'), 'all' => __('All Time')] as $value => $label)
                <button
                    wire:click="$set('period', '{{ $value }}')"
                    class="rounded-full px-3 py-1 font-medium {{ $period === $value ? 'bg-brand-700 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}"
                >
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>

    <div class="mt-4 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="rounded-lg border border-gray-200 bg-white p-4">
            <p class="text-sm text-gray-500">{{ __('Revenue') }}</p>
            <p class="mt-1 text-xl font-semibold text-gray-900">KSh {{ number_format($revenue, 2) }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4">
            <p class="text-sm text-gray-500">{{ __('Cost of Goods Sold') }}</p>
            <p class="mt-1 text-xl font-semibold text-gray-900">KSh {{ number_format($cogs, 2) }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4">
            <p class="text-sm text-gray-500">{{ __('Gross Profit') }}</p>
            <p @class(['mt-1 text-xl font-semibold', 'text-brand-700' => $profit >= 0, 'text-red-600' => $profit < 0])>
                KSh {{ number_format($profit, 2) }}
            </p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4">
            <p class="text-sm text-gray-500">{{ __('Profit Margin') }}</p>
            <p @class(['mt-1 text-xl font-semibold', 'text-brand-700' => $margin >= 0, 'text-red-600' => $margin < 0])>
                {{ number_format($margin * 100, 1) }}%
            </p>
        </div>
    </div>

    <div class="mt-4 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="rounded-lg border border-gray-200 bg-white p-4">
            <p class="text-sm text-gray-500">{{ __('Stock Bought to Date') }}</p>
            <p class="mt-1 text-lg font-semibold text-gray-900">KSh {{ number_format($stockBoughtToDate, 2) }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4">
            <p class="text-sm text-gray-500">{{ __('Current Stock Value') }}</p>
            <p class="mt-1 text-lg font-semibold text-gray-900">KSh {{ number_format($currentStockValue, 2) }}</p>
        </div>
        <a href="{{ route('stock-balance.index') }}" class="rounded-lg border border-gray-200 bg-white p-4 hover:border-accent-300">
            <p class="text-sm text-gray-500">{{ __('Items Low on Stock') }}</p>
            <p class="mt-1 text-lg font-semibold text-accent-600">{{ $lowStockCount }}</p>
        </a>
        <a href="{{ route('stock-balance.index') }}" class="rounded-lg border border-gray-200 bg-white p-4 hover:border-red-300">
            <p class="text-sm text-gray-500">{{ __('Items Out of Stock') }}</p>
            <p class="mt-1 text-lg font-semibold text-red-600">{{ $outOfStockCount }}</p>
        </a>
    </div>

    <div class="mt-8">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h3 class="font-semibold text-gray-900">{{ __('Revenue & Profit Trend') }}</h3>
            <div class="flex gap-2 text-sm">
                @foreach (['daily' => __('Daily'), 'monthly' => __('Monthly'), 'yearly' => __('Yearly')] as $value => $label)
                    <button
                        wire:click="$set('rollup', '{{ $value }}')"
                        class="rounded-full px-3 py-1 font-medium {{ $rollup === $value ? 'bg-brand-700 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        <div class="mt-4 overflow-x-auto rounded-lg border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr class="text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                        <th class="px-4 py-3">{{ __('Period') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Revenue') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Cost') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Profit / Loss') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse ($trend as $row)
                        <tr wire:key="trend-{{ $row['key'] }}">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $row['label'] }}</td>
                            <td class="px-4 py-3 text-right text-gray-600">{{ number_format($row['revenue'], 2) }}</td>
                            <td class="px-4 py-3 text-right text-gray-600">{{ number_format($row['cogs'], 2) }}</td>
                            <td class="px-4 py-3 text-right font-medium {{ $row['profit'] >= 0 ? 'text-brand-700' : 'text-red-600' }}">
                                {{ number_format($row['profit'], 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500">{{ __('No sales recorded yet.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div>
            <h3 class="font-semibold text-gray-900">{{ __('Revenue by Item') }}</h3>
            <div class="mt-4 overflow-x-auto rounded-lg border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr class="text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                            <th class="px-4 py-3">{{ __('Item') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('Revenue') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('Profit') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($itemBreakdown as $item)
                            <tr wire:key="item-breakdown-{{ $item->id }}">
                                <td class="px-4 py-3 text-gray-900">{{ $item->name }}</td>
                                <td class="px-4 py-3 text-right text-gray-600">{{ number_format($item->revenue, 2) }}</td>
                                <td class="px-4 py-3 text-right {{ $item->profit >= 0 ? 'text-brand-700' : 'text-red-600' }}">{{ number_format($item->profit, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-8 text-center text-sm text-gray-500">{{ __('No sales recorded yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div>
            <h3 class="font-semibold text-gray-900">{{ __('Stock Value by Category') }}</h3>
            <div class="mt-4 overflow-x-auto rounded-lg border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr class="text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                            <th class="px-4 py-3">{{ __('Category') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('Stock Value') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($categoryBreakdown as $category => $value)
                            <tr wire:key="category-breakdown-{{ $category }}">
                                <td class="px-4 py-3 text-gray-900">{{ $category }}</td>
                                <td class="px-4 py-3 text-right text-gray-600">{{ number_format($value, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="px-4 py-8 text-center text-sm text-gray-500">{{ __('No items yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
