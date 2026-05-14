<x-filament-panels::page>
    {{-- Filter --}}
    <div class="mb-6">
        <form wire:submit.prevent="$refresh">
            {{ $this->form }}

            <div class="flex gap-2 mt-4">
                <x-filament::button type="submit">
                    Apply Filter
                </x-filament::button>

                <x-filament::button wire:click="export" color="success">
                    <x-heroicon-o-arrow-down-tray class="w-4 h-4 mr-2" />
                    Export Excel
                </x-filament::button>
            </div>
        </form>
    </div>

    {{-- Summary Cards --}}
    @php $summary = $this->getSummary(); @endphp
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <x-filament::section>
            <div class="text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">Total Orders</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white">
                    {{ number_format($summary['total_orders']) }}
                </p>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">Total Revenue</p>
                <p class="text-3xl font-bold text-green-600 dark:text-green-400">
                    Rp {{ number_format($summary['total_revenue'], 0, ',', '.') }}
                </p>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">Average Order</p>
                <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">
                    Rp {{ number_format($summary['average_order'], 0, ',', '.') }}
                </p>
            </div>
        </x-filament::section>
    </div>

    {{-- Table --}}
    {{ $this->table }}
</x-filament-panels::page>
