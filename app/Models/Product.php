<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'price',
        'stock',
        'image',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Auto-generate slug saat creating
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
        });
    }

    /**
     * Relationship: Product belongs to Category
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Relationship: Product has many OrderItems
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Check if product is in stock
     */
    public function inStock(): bool
    {
        return $this->stock > 0;
    }

    /**
     * Reduce stock after order
     */
    public function reduceStock(int $quantity): void
    {
        $this->decrement('stock', $quantity);
    }

    /**
     * Increase stock (for restocking or order cancellation)
     */
    public function increaseStock(int $quantity): void
    {
        $this->increment('stock', $quantity);
    }

    /**
     * Get formatted price (Rp)
     */
    public function getFormattedPriceAttribute(): string
    {
        return 'Rp ' . number_format($this->price, 0, ',', '.');
    }

    /**
     * Scope: Only active products
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Only products with stock
     */
    public function scopeInStock($query)
    {
        return $query->where('stock', '>', 0);
    }

    /**
     * Scope: Low stock products (stock <= 10)
     */
    public function scopeLowStock($query)
    {
        return $query->where('stock', '<=', 10)->where('stock', '>', 0);
    }
}
