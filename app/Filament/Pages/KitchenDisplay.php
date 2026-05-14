<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Models\Order;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\Attributes\On;

class KitchenDisplay extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-fire';

    protected static ?string $navigationLabel = 'Kitchen Display';

    protected static ?string $title = 'Kitchen Display';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.kitchen-display';

    /**
     * Only Kitchen and Manager can access the Kitchen Display.
     */
    public static function canAccess(): bool
    {
        return in_array(auth()->user()?->role, [UserRole::Kitchen, UserRole::Manager]);
    }

    /**
     * Get all active orders (pending + processing) for today,
     * ordered by status (pending first) then by time.
     */
    public function getOrders()
    {
        return Order::with('items.product')
            ->active()
            ->today()
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Mark an order as processing (Kitchen starts cooking).
     */
    public function startCooking(int $orderId): void
    {
        $order = Order::findOrFail($orderId);

        if ($order->status !== 'pending') {
            return;
        }

        $order->markAsProcessing();

        Notification::make()
            ->title('Cooking Started')
            ->body("Order {$order->invoice_number} is being prepared.")
            ->warning()
            ->send();
    }

    /**
     * Mark an order as completed (food is ready to serve).
     */
    public function markReady(int $orderId): void
    {
        $order = Order::findOrFail($orderId);

        if ($order->status !== 'processing') {
            return;
        }

        $order->markAsCompleted();

        Notification::make()
            ->title('Order Ready! 🍽️')
            ->body("Order {$order->invoice_number} is ready to serve.")
            ->success()
            ->send();
    }
}
