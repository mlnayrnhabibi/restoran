<x-filament-panels::page>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Products Section (Left/Center) --}}
        <div class="lg:col-span-2 space-y-4">

            {{-- Search & Filter --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4">
                <div class="flex flex-col sm:flex-row gap-4">
                    {{-- Search --}}
                    <div class="flex-1">
                        <input type="text" wire:model.live.debounce.300ms="searchProduct"
                            placeholder="Search products..."
                            class="w-full px-4 py-2 rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white focus:ring-primary-500 focus:border-primary-500">
                    </div>
                </div>

                {{-- Category Filter --}}
                <div class="flex flex-wrap gap-2 mt-4">
                    <button wire:click="selectCategory(null)"
                        class="px-3 py-1.5 rounded-full text-sm font-medium transition
                            {{ !$this->selectedCategory ? 'bg-primary-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200' }}">
                        All
                    </button>
                    @foreach ($this->categories as $category)
                        <button wire:click="selectCategory({{ $category->id }})"
                            class="px-3 py-1.5 rounded-full text-sm font-medium transition
                                {{ $this->selectedCategory === $category->id ? 'bg-primary-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200' }}">
                            {{ $category->name }} ({{ $category->products_count }})
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Product Grid --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4">
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                    @forelse($this->products as $product)
                        <div wire:click="addToCart({{ $product->id }})" wire:key="product-{{ $product->id }}"
                            class="cursor-pointer bg-gray-50 dark:bg-gray-700 rounded-xl p-3 hover:ring-2 hover:ring-primary-500 transition group">
                            {{-- Image --}}
                            <div class="aspect-square rounded-lg overflow-hidden mb-3 bg-gray-200 dark:bg-gray-600">
                                @if ($product->image)
                                    <img src="{{ Storage::temporaryUrl($product->image, now()->addMinutes(5)) }}"
                                        alt="{{ $product->name }}"
                                        class="w-full h-full object-cover group-hover:scale-105 transition">
                                @else
                                    <div class="w-full h-full flex items-center justify-center">
                                        <x-heroicon-o-photo class="w-10 h-10 text-gray-400" />
                                    </div>
                                @endif
                            </div>

                            {{-- Info --}}
                            <h4 class="font-medium text-sm text-gray-900 dark:text-white truncate">
                                {{ $product->name }}
                            </h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">
                                {{ $product->category?->name }}
                            </p>
                            <p class="text-primary-600 dark:text-primary-400 font-bold">
                                Rp {{ number_format($product->price, 0, ',', '.') }}
                            </p>
                            <p class="text-xs {{ $product->stock <= 10 ? 'text-orange-500' : 'text-gray-500' }}">
                                Stock: {{ $product->stock }}
                            </p>
                        </div>
                    @empty
                        <div class="col-span-full py-12 text-center text-gray-500">
                            <x-heroicon-o-cube class="w-12 h-12 mx-auto mb-3 opacity-50" />
                            <p>No products found</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Cart Section (Right) --}}
        <div class="lg:col-span-1">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 lg:sticky lg:top-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                        Shopping Cart
                    </h3>
                    <span
                        class="bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-200 text-sm font-medium px-2.5 py-0.5 rounded-full">
                        {{ $this->cartItemsCount }} items
                    </span>
                </div>

                {{-- Customer Name --}}
                <div class="mb-4">
                    <input type="text" wire:model="customerName" placeholder="Customer name (optional)"
                        class="w-full px-4 py-2 rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm">
                </div>

                {{-- Cart Items --}}
                <div class="space-y-3 mb-4 max-h-[400px] overflow-y-auto">
                    @forelse($this->cart as $index => $item)
                        <div wire:key="cart-{{ $index }}"
                            class="flex gap-3 bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
                            {{-- Product Info --}}
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-sm text-gray-900 dark:text-white truncate">
                                    {{ $item['name'] }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    Rp {{ number_format($item['price'], 0, ',', '.') }}
                                </p>
                                <p class="text-sm font-semibold text-primary-600 dark:text-primary-400 mt-1">
                                    Rp {{ number_format($item['price'] * $item['quantity'], 0, ',', '.') }}
                                </p>
                            </div>

                            {{-- Quantity Controls --}}
                            <div class="flex items-center gap-2">
                                <button wire:click="decrementQty({{ $index }})"
                                    class="w-8 h-8 rounded-lg bg-gray-200 dark:bg-gray-600 flex items-center justify-center hover:bg-gray-300 transition">
                                    <x-heroicon-o-minus class="w-4 h-4" />
                                </button>
                                <span class="w-8 text-center text-sm font-medium">
                                    {{ $item['quantity'] }}
                                </span>
                                <button wire:click="incrementQty({{ $index }})"
                                    class="w-8 h-8 rounded-lg bg-gray-200 dark:bg-gray-600 flex items-center justify-center hover:bg-gray-300 transition">
                                    <x-heroicon-o-plus class="w-4 h-4" />
                                </button>
                            </div>

                            {{-- Remove --}}
                            <button wire:click="removeFromCart({{ $index }})"
                                class="text-red-500 hover:text-red-700 transition">
                                <x-heroicon-o-trash class="w-5 h-5" />
                            </button>
                        </div>
                    @empty
                        <div class="py-8 text-center text-gray-500">
                            <x-heroicon-o-shopping-cart class="w-12 h-12 mx-auto mb-3 opacity-50" />
                            <p>Cart is empty</p>
                            <p class="text-xs mt-1">Click products to add</p>
                        </div>
                    @endforelse
                </div>

                {{-- Total --}}
                <div class="border-t dark:border-gray-700 pt-4 mb-4">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600 dark:text-gray-400">Total</span>
                        <span class="text-2xl font-bold text-primary-600 dark:text-primary-400">
                            Rp {{ number_format($this->cartTotal, 0, ',', '.') }}
                        </span>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="space-y-2">
                    <button wire:click="checkout" wire:loading.attr="disabled"
                        @if ($this->cart->isEmpty()) disabled @endif
                        class="w-full py-3 bg-primary-600 text-white rounded-lg font-medium hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed transition flex items-center justify-center gap-2">
                        <div wire:loading.remove wire:target="checkout" class="flex items-center gap-2">
                            <x-heroicon-o-check class="w-5 h-5" />
                            <span>Complete Order</span>
                        </div>

                        <div wire:loading.flex wire:target="checkout" class="items-center gap-2">
                            <svg class="w-5 h-5 animate-spin" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4" fill="none" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                            </svg>

                            <span>Processing...</span>
                        </div>
                    </button>

                    <button wire:click="clearCart" @if ($this->cart->isEmpty()) disabled @endif
                        class="w-full py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg text-sm hover:bg-gray-300 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed transition">
                        Clear Cart
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
