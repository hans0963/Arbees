<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\InventoryLog;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InventoryController extends Controller
{
    public function index()
    {
        $inventories = Inventory::with('product')->latest()->get();
        return view('inventory.index', compact('inventories'));
    }

    public function show(Product $product)
    {
        $inventory = $product->inventory;
        $logs = $product->inventoryLogs()->with('user')->latest()->paginate(10);
        return view('inventory.show', compact('product', 'inventory', 'logs'));
    }

    public function adjust(Request $request, Product $product)
    {
        $validated = $request->validate([
            'type'            => 'required|in:restock,adjustment,waste',
            'quantity_change' => 'required|integer|not_in:0',
            'note'            => 'nullable|string|max:255',
        ]);

        $inventory = $product->inventory;
        $before    = $inventory->quantity;
        $after     = max(0, $before + $validated['quantity_change']);

        $inventory->update(['quantity' => $after]);

        InventoryLog::create([
            'product_id'      => $product->id,
            'user_id'         => Auth::id(),
            'type'            => $validated['type'],
            'quantity_change' => $validated['quantity_change'],
            'quantity_before' => $before,
            'quantity_after'  => $after,
            'note'            => $validated['note'] ?? null,
        ]);

        return redirect()->back()->with('success', 'Inventory adjusted successfully.');
    }

    public function lowStock()
    {
        $inventories = Inventory::with('product')
            ->whereColumn('quantity', '<=', 'low_stock_threshold')
            ->get();

        return view('inventory.low-stock', compact('inventories'));
    }
}