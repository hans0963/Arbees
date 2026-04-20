<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Inventory;
use App\Models\InventoryLog;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderItemController extends Controller
{
    /**
     * List all items under a specific order.
     */
    public function index(Order $order)
    {
        $items = $order->orderItems()->with('product')->get();
        return view('order-items.index', compact('order', 'items'));
    }

    /**
     * Add a new item to an existing order.
     */
    public function store(Request $request, Order $order)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|integer|min:1',
        ]);

        DB::transaction(function () use ($validated, $order) {
            $product   = Product::findOrFail($validated['product_id']);
            $lineTotal = $product->price * $validated['quantity'];

            // Check if item already exists in the order — update instead
            $existing = $order->orderItems()
                ->where('product_id', $product->id)
                ->first();

            if ($existing) {
                $newQty      = $existing->quantity + $validated['quantity'];
                $newSubtotal = $product->price * $newQty;
                $existing->update([
                    'quantity' => $newQty,
                    'subtotal' => $newSubtotal,
                ]);
            } else {
                $order->orderItems()->create([
                    'product_id' => $product->id,
                    'quantity'   => $validated['quantity'],
                    'unit_price' => $product->price,
                    'subtotal'   => $lineTotal,
                ]);
            }

            // Deduct inventory
            $inventory = Inventory::where('product_id', $product->id)->first();
            if ($inventory) {
                $before = $inventory->quantity;
                $after  = max(0, $before - $validated['quantity']);
                $inventory->update(['quantity' => $after]);

                InventoryLog::create([
                    'product_id'      => $product->id,
                    'user_id'         => Auth::id(),
                    'type'            => 'sale',
                    'quantity_change' => -$validated['quantity'],
                    'quantity_before' => $before,
                    'quantity_after'  => $after,
                    'note'            => 'Added to Order #' . $order->order_number,
                ]);
            }

            // Recalculate order totals
            $order->subtotal = $order->orderItems()->sum('subtotal');
            $order->total    = $order->subtotal - $order->discount;
            $order->save();
        });

        return redirect()->route('orders.show', $order)
            ->with('success', 'Item added to order successfully.');
    }

    /**
     * Update the quantity of a specific order item.
     */
    public function update(Request $request, Order $order, OrderItem $orderItem)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        DB::transaction(function () use ($validated, $order, $orderItem) {
            $product     = $orderItem->product;
            $oldQty      = $orderItem->quantity;
            $newQty      = $validated['quantity'];
            $qtyDiff     = $newQty - $oldQty; // positive = more, negative = less

            $orderItem->update([
                'quantity' => $newQty,
                'subtotal' => $product->price * $newQty,
            ]);

            // Adjust inventory based on quantity difference
            $inventory = Inventory::where('product_id', $product->id)->first();
            if ($inventory && $qtyDiff !== 0) {
                $before = $inventory->quantity;
                $after  = max(0, $before - $qtyDiff);
                $inventory->update(['quantity' => $after]);

                InventoryLog::create([
                    'product_id'      => $product->id,
                    'user_id'         => Auth::id(),
                    'type'            => 'adjustment',
                    'quantity_change' => -$qtyDiff,
                    'quantity_before' => $before,
                    'quantity_after'  => $after,
                    'note'            => 'Item qty updated on Order #' . $order->order_number,
                ]);
            }

            // Recalculate order totals
            $order->subtotal = $order->orderItems()->sum('subtotal');
            $order->total    = $order->subtotal - $order->discount;
            $order->save();
        });

        return redirect()->route('orders.show', $order)
            ->with('success', 'Order item updated successfully.');
    }

    /**
     * Remove an item from an order and restore inventory.
     */
    public function destroy(Order $order, OrderItem $orderItem)
    {
        DB::transaction(function () use ($order, $orderItem) {
            $product = $orderItem->product;

            // Restore inventory
            $inventory = Inventory::where('product_id', $product->id)->first();
            if ($inventory) {
                $before = $inventory->quantity;
                $after  = $before + $orderItem->quantity;
                $inventory->update(['quantity' => $after]);

                InventoryLog::create([
                    'product_id'      => $product->id,
                    'user_id'         => Auth::id(),
                    'type'            => 'adjustment',
                    'quantity_change' => +$orderItem->quantity,
                    'quantity_before' => $before,
                    'quantity_after'  => $after,
                    'note'            => 'Item removed from Order #' . $order->order_number,
                ]);
            }

            $orderItem->delete();

            // Recalculate order totals
            $order->subtotal = $order->orderItems()->sum('subtotal');
            $order->total    = $order->subtotal - $order->discount;
            $order->save();
        });

        return redirect()->route('orders.show', $order)
            ->with('success', 'Item removed from order.');
    }
}