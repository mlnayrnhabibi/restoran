<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use BackedEnum;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

class PosCashier extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-computer-desktop';

    protected static ?string $navigationLabel = 'POS Cashier';

    protected static ?string $title = 'Point of Sale';

    protected static ?int $navigationSort = 0;

    protected string $view = 'filament.pages.pos-cashier';

    /**
     * Only Cashier and Manager can access the POS page.
     */
    public static function canAccess(): bool
    {
        return in_array(auth()->user()?->role, [UserRole::Cashier, UserRole::Manager]);
    }

    public Collection $cart;

    public string $customerName = '';

    public string $searchProduct = '';

    public ?int $selectedCategory = null;

    public function mount(): void
    {
        $this->cart = collect();
    }

    #[Computed]
    public function categories(): Collection
    {
        return Category::where('is_active', true)
            ->withCount('products')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function products(): Collection
    {
        return Product::query()
            ->where('is_active', true)
            ->where('stock', '>', 0)
            ->when($this->searchProduct, function ($query) {
                $query->where('name', 'like', '%'.$this->searchProduct.'%');
            })
            ->when($this->selectedCategory, function ($query) {
                $query->where('category_id', $this->selectedCategory);
            })
            ->with('category')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function cartTotal(): float
    {
        return $this->cart->sum(fn ($item) => $item['price'] * $item['quantity']);
    }

    #[Computed]
    public function cartItemsCount(): int
    {
        return $this->cart->sum('quantity');
    }

    public function selectCategory(?int $categoryId): void
    {
        $this->selectedCategory = $categoryId === $this->selectedCategory ? null : $categoryId;
    }

    public function addToCart(int $productId): void
    {
        $product = Product::find($productId);

        if (! $product || $product->stock <= 0) {
            Notification::make()
                ->title('Product out of stock!')
                ->danger()
                ->send();

            return;
        }

        $existingIndex = $this->cart->search(fn ($item) => $item['product_id'] === $productId);

        if ($existingIndex !== false) {
            $currentQty = $this->cart[$existingIndex]['quantity'];

            if ($currentQty >= $product->stock) {
                Notification::make()
                    ->title('Not enough stock!')
                    ->body("Available: {$product->stock}")
                    ->danger()
                    ->send();

                return;
            }

            $cart = $this->cart->toArray();
            $cart[$existingIndex]['quantity']++;
            $this->cart = collect($cart);
        } else {
            $this->cart->push([
                'product_id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'quantity' => 1,
                'stock' => $product->stock,
                'image' => $product->image,
            ]);
        }

        Notification::make()
            ->title('Added to cart')
            ->success()
            ->duration(1000)
            ->send();
    }

    public function removeFromCart(int $index): void
    {
        $cart = $this->cart->toArray();
        unset($cart[$index]);
        $this->cart = collect(array_values($cart));
    }

    public function updateQuantity(int $index, int $quantity): void
    {
        if ($quantity <= 0) {
            $this->removeFromCart($index);

            return;
        }

        $cart = $this->cart->toArray();
        $item = $cart[$index];

        if ($quantity > $item['stock']) {
            Notification::make()
                ->title('Not enough stock!')
                ->body("Available: {$item['stock']}")
                ->danger()
                ->send();

            return;
        }

        $cart[$index]['quantity'] = $quantity;
        $this->cart = collect($cart);
    }

    public function incrementQty(int $index): void
    {
        $cart = $this->cart->toArray();
        $newQty = $cart[$index]['quantity'] + 1;
        $this->updateQuantity($index, $newQty);
    }

    public function decrementQty(int $index): void
    {
        $cart = $this->cart->toArray();
        $newQty = $cart[$index]['quantity'] - 1;
        $this->updateQuantity($index, $newQty);
    }

    public function checkout(): void
    {
        if ($this->cart->isEmpty()) {
            Notification::make()
                ->title('Cart is empty!')
                ->danger()
                ->send();

            return;
        }

        // Create order — pending status so Kitchen can process it
        $order = Order::create([
            'customer_name' => $this->customerName ?: null,
            'total_amount' => $this->cartTotal,
            'status' => 'pending',
        ]);

        // Create order items (stock will be reduced via model events)
        foreach ($this->cart as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'subtotal' => $item['price'] * $item['quantity'],
            ]);
        }

        // Clear cart
        $this->cart = collect();
        $this->customerName = '';

        Notification::make()
            ->title('Order placed!')
            ->body("Invoice: {$order->invoice_number} — sent to kitchen.")
            ->success()
            ->send();
    }

    public function clearCart(): void
    {
        $this->cart = collect();
        $this->customerName = '';

        Notification::make()
            ->title('Cart cleared')
            ->send();
    }
}
