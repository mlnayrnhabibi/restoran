<x-filament-panels::page wire:poll.3s>
    @php
        $orders = $this->getOrders();
    @endphp

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">
                Kitchen Display
            </h1>

            <p class="text-sm text-gray-500 dark:text-gray-400">
                Live kitchen order monitoring
            </p>
        </div>

        <div class="flex gap-3">
            <x-filament::badge color="warning" size="lg">
                {{ $orders->where('status', 'pending')->count() }} Pending
            </x-filament::badge>

            <x-filament::badge color="info" size="lg">
                {{ $orders->where('status', 'processing')->count() }} Cooking
            </x-filament::badge>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($orders as $order)
            <x-filament::section
                :heading="$order->invoice_number"
                compact
                @class([
                    'border-warning-500 dark:border-warning-400' => $order->status === 'pending',
                    'border-info-500 dark:border-info-400' => $order->status === 'processing',
                ])
            >
                <x-slot name="description">
                    <div class="flex items-center justify-between">
                        <div>
                            {{ $order->customer_name ?? 'Walk-in Customer' }}
                        </div>

                        <x-filament::badge :color="$order->status === 'pending' ? 'warning' : 'info'">
                            {{ ucfirst($order->status) }}
                        </x-filament::badge>
                    </div>
                </x-slot>

                <div class="space-y-3">
                    <div class="space-y-2">
                        @foreach ($order->items as $item)
                            <div class="flex items-center gap-3">
                                <x-filament::badge color="gray">
                                    {{ $item->quantity }}x
                                </x-filament::badge>

                                <span class="font-medium">
                                    {{ $item->product->name }}
                                </span>
                            </div>
                        @endforeach
                    </div>

                    @if ($order->notes)
                        <div class="p-3 text-sm rounded-xl bg-warning-50 dark:bg-warning-500/10">
                            {{ $order->notes }}
                        </div>
                    @endif

                    <div class="pt-2">
                        @if ($order->status === 'pending')
                            <x-filament::button
                                color="warning"
                                class="w-full"
                                wire:click="startCooking({{ $order->id }})"
                                wire:loading.attr="disabled"
                            >
                                Start Cooking
                            </x-filament::button>
                        @elseif($order->status === 'processing')
                            <x-filament::button
                                color="success"
                                class="w-full"
                                wire:click="markReady({{ $order->id }})"
                                wire:loading.attr="disabled"
                            >
                                Ready to Serve
                            </x-filament::button>
                        @endif
                    </div>
                </div>
            </x-filament::section>
        @empty
            <div class="col-span-full">
                <x-filament::section>
                    <div class="py-16 text-center">
                        <h2 class="text-2xl font-bold">
                            All Clear!
                        </h2>

                        <p class="text-gray-500 dark:text-gray-400 mt-2">
                            No pending orders right now.
                        </p>
                    </div>
                </x-filament::section>
            </div>
        @endforelse
    </div>
</x-filament-panels::page>