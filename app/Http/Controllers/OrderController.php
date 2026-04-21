<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\InventoryLog;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    // ── Shared (admin + shop) ──────────────────────────────

    public function index()
    {
        $orders = Order::with('user')
            ->when(request()->is('shop/*'), fn($q) => $q->where('user_id', Auth::id()))
            ->latest()
            ->paginate(15);

        if (request()->is('shop/*')) {
            return view('shop.orders', compact('orders'));
        }

        return view('admin.orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        $order->load(['user', 'orderItems.product']);

        if (request()->is('shop/*')) {
            return view('shop.order-show', compact('order'));
        }

        return view('admin.orders.show', compact('order'));
    }

    // ── Shop (customer-facing) ─────────────────────────────

    public function create()
    {
        $products = Product::with('inventory')
            ->where('is_available', true)
            ->get();

        if (request()->is('shop/*')) {
            return view('shop.cart', compact('products'));
        }

        return view('admin.orders.create', compact('products'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type'                   => 'required|in:walk_in,online',
            'items'                  => 'required|array|min:1',
            'items.*.product_id'     => 'required|exists:products,id',
            'items.*.quantity'       => 'required|integer|min:1',
            'discount'               => 'nullable|numeric|min:0',
            'payment_method'         => 'nullable|in:cash,gcash,card',
            'notes'                  => 'nullable|string',
            'delivery_address'       => 'nullable|string|required_if:type,online',
        ]);

        $order = DB::transaction(function () use ($validated) {
            $order = Order::create([
                'user_id'          => Auth::id(),
                'type'             => $validated['type'],
                'status'           => 'pending',
                'subtotal'         => 0,
                'discount'         => $validated['discount'] ?? 0,
                'total'            => 0,
                'payment_method'   => $validated['payment_method'] ?? null,
                'payment_status'   => 'unpaid',
                'notes'            => $validated['notes'] ?? null,
                'delivery_address' => $validated['delivery_address'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                $product   = Product::findOrFail($item['product_id']);
                $lineTotal = $product->price * $item['quantity'];

                $order->orderItems()->create([
                    'product_id' => $product->id,
                    'quantity'   => $item['quantity'],
                    'unit_price' => $product->price,
                    'subtotal'   => $lineTotal,
                ]);

                $inventory = Inventory::where('product_id', $product->id)->first();
                if ($inventory) {
                    $before = $inventory->quantity;
                    $after  = max(0, $before - $item['quantity']);
                    $inventory->update(['quantity' => $after]);

                    InventoryLog::create([
                        'product_id'      => $product->id,
                        'user_id'         => Auth::id(),
                        'type'            => 'sale',
                        'quantity_change' => -$item['quantity'],
                        'quantity_before' => $before,
                        'quantity_after'  => $after,
                        'note'            => 'Order #' . $order->order_number,
                    ]);
                }
            }

            $order->subtotal = $order->orderItems()->sum('subtotal');
            $order->total    = $order->subtotal - $order->discount;
            $order->save();

            return $order;
        });

        // Redirect to correct context
        if (request()->is('shop/*')) {
            return redirect()->route('shop.orders.show', $order)
                ->with('success', 'Order placed successfully.');
        }

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'Order placed successfully.');
    }

    // ── Admin only ─────────────────────────────────────────

    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,preparing,ready,completed,cancelled',
        ]);

        $order->update($validated);

        return redirect()->back()->with('success', 'Order status updated.');
    }

    public function markAsPaid(Order $order)
    {
        $order->update(['payment_status' => 'paid']);

        return redirect()->back()->with('success', 'Order marked as paid.');
    }

    public function cancel(Order $order)
    {
        DB::transaction(function () use ($order) {
            foreach ($order->orderItems as $item) {
                $inventory = Inventory::where('product_id', $item->product_id)->first();
                if ($inventory) {
                    $before = $inventory->quantity;
                    $after  = $before + $item->quantity;
                    $inventory->update(['quantity' => $after]);

                    InventoryLog::create([
                        'product_id'      => $item->product_id,
                        'user_id'         => Auth::id(),
                        'type'            => 'adjustment',
                        'quantity_change' => +$item->quantity,
                        'quantity_before' => $before,
                        'quantity_after'  => $after,
                        'note'            => 'Order #' . $order->order_number . ' cancelled',
                    ]);
                }
            }

            $order->update(['status' => 'cancelled']);
        });

        return redirect()->back()->with('success', 'Order cancelled and inventory restored.');
    }

    public function destroy(Order $order)
    {
        $order->delete();

        return redirect()->route('admin.orders.index')
            ->with('success', 'Order deleted.');
    }
}