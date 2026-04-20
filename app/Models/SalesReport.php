<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_date',
        'period',
        'total_orders',
        'total_revenue',
        'total_discount',
        'total_items_sold',
        'generated_by',
    ];

    protected $casts = [
        'report_date'    => 'date',
        'total_revenue'  => 'decimal:2',
        'total_discount' => 'decimal:2',
        'total_orders'   => 'integer',
        'total_items_sold' => 'integer',
    ];

    // ── Relationships ──────────────────────────────────────

    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}