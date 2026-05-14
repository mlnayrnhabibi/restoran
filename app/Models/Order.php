<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'invoice_number',
        'customer_name',
        'total_amount',
        'status',
        'notes',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
    ];

    /**
     * Auto-generate invoice number saat creating
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->invoice_number)) {
                $order->invoice_number = self::generateInvoiceNumber();
            }
        });
    }

    /**
     * Generate unique invoice number
     * Format: INV-YYYYMMDD-XXXX
     */
    public static function generateInvoiceNumber(): string
    {
        $today = now()->format('Ymd');
        $count = self::whereDate('created_at', today())->count() + 1;

        return 'INV-' . $today . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Relationship: Order has many OrderItems
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Calculate and update total from items
     */
    public function calculateTotal(): void
    {
        $this->total_amount = $this->items->sum('subtotal');
        $this->save();
    }

    /**
     * Get formatted total (Rp)
     */
    public function getFormattedTotalAttribute(): string
    {
        return 'Rp ' . number_format($this->total_amount, 0, ',', '.');
    }

    /**
     * Mark order as being processed by kitchen
     */
    public function markAsProcessing(): void
    {
        $this->update(['status' => 'processing']);
    }

    /**
     * Mark order as completed (ready to serve)
     */
    public function markAsCompleted(): void
    {
        $this->update(['status' => 'completed']);
    }

    /**
     * Mark order as cancelled and restore stock
     */
    public function markAsCancelled(): void
    {
        foreach ($this->items as $item) {
            $item->product->increaseStock($item->quantity);
        }

        $this->update(['status' => 'cancelled']);
    }

    /** Scope: Completed orders only */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /** Scope: Pending orders only */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /** Scope: Processing orders (kitchen is cooking) */
    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    /** Scope: Active orders — pending or processing */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'processing']);
    }

    /** Scope: Today's orders */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /** Scope: This month's orders */
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
                     ->whereYear('created_at', now()->year);
    }
}
