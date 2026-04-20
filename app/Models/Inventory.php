<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'quantity',
        'low_stock_threshold',
    ];

    protected $casts = [
        'quantity'            => 'integer',
        'low_stock_threshold' => 'integer',
    ];

    // ── Relationships ──────────────────────────────────────

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // ── Helpers ────────────────────────────────────────────

    public function isLowStock(): bool
    {
        return $this->quantity <= $this->low_stock_threshold;
    }

    public function isOutOfStock(): bool
    {
        return $this->quantity <= 0;
    }
}