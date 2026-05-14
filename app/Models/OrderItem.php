<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
     protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'price',
        'subtotal',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    /**
     * Boot method untuk auto-calculate dan stock reduction
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-calculate subtotal before creating
        static::creating(function ($item) {
            $item->subtotal = $item->price * $item->quantity;
        });

        // Auto-calculate subtotal before updating
        static::updating(function ($item) {
            $item->subtotal = $item->price * $item->quantity;
        });

        // Reduce product stock after item created
        static::created(function ($item) {
            if ($item->product) {
                $item->product->reduceStock($item->quantity);
            }
        });

        // Update order total after item created
        static::saved(function ($item) {
            if ($item->order) {
                $item->order->calculateTotal();
            }
        });

        // Restore stock and recalculate total when item deleted
        static::deleting(function ($item) {
            if ($item->product && $item->order->status !== 'cancelled') {
                $item->product->increaseStock($item->quantity);
            }
        });

        static::deleted(function ($item) {
            if ($item->order) {
                $item->order->calculateTotal();
            }
        });
    }

    /**
     * Relationship: OrderItem belongs to Order
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Relationship: OrderItem belongs to Product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get formatted subtotal (Rp)
     */
    public function getFormattedSubtotalAttribute(): string
    {
        return 'Rp ' . number_format($this->subtotal, 0, ',', '.');
    }
}
