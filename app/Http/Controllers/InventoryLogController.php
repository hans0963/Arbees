<?php

namespace App\Http\Controllers;

use App\Models\InventoryLog;
use App\Models\Product;
use Illuminate\Http\Request;

class InventoryLogController extends Controller
{
    /**
     * List all inventory logs (filterable by type, product, or date).
     */
    public function index(Request $request)
    {
        $query = InventoryLog::with(['product', 'user'])->latest();

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $logs     = $query->paginate(20)->withQueryString();
        $products = Product::orderBy('name')->get();

        return view('inventory-logs.index', compact('logs', 'products'));
    }

    /**
     * Show logs for a specific product.
     */
    public function forProduct(Product $product)
    {
        $logs = $product->inventoryLogs()
            ->with('user')
            ->latest()
            ->paginate(20);

        return view('inventory-logs.product', compact('product', 'logs'));
    }

    /**
     * Show a single log entry.
     */
    public function show(InventoryLog $inventoryLog)
    {
        $inventoryLog->load(['product', 'user']);
        return view('inventory-logs.show', compact('inventoryLog'));
    }
}