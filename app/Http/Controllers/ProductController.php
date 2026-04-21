<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    // ── Shop (customer-facing) ─────────────────────────────

    public function index()
    {
        $products = Product::with(['category', 'inventory'])
            ->where('is_available', true)
            ->latest()
            ->get();

        // Detect context: shop vs admin
        if (request()->is('shop/*') || request()->is('shop')) {
            return view('shop.index', compact('products'));
        }

        return view('admin.products.index', compact('products'));
    }

    public function show(Product $product)
    {
        $product->load(['category', 'inventory', 'inventoryLogs']);

        if (request()->is('shop/*')) {
            return view('shop.product', compact('product'));
        }

        return view('admin.products.show', compact('product'));
    }

    // ── Admin ──────────────────────────────────────────────

    public function create()
    {
        $categories = Category::all();
        return view('admin.products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id'         => 'required|exists:categories,id',
            'name'                => 'required|string|max:255',
            'description'         => 'nullable|string',
            'price'               => 'required|numeric|min:0',
            'image'               => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'is_available'        => 'boolean',
            'quantity'            => 'required|integer|min:0',
            'low_stock_threshold' => 'required|integer|min:1',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        $product = Product::create($validated);

        \App\Models\Inventory::create([
            'product_id'          => $product->id,
            'quantity'            => $validated['quantity'],
            'low_stock_threshold' => $validated['low_stock_threshold'],
        ]);

        return redirect()->route('admin.products.index')
            ->with('success', 'Product created successfully.');
    }

    public function edit(Product $product)
    {
        $categories = Category::all();
        return view('admin.products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'category_id'  => 'required|exists:categories,id',
            'name'         => 'required|string|max:255',
            'description'  => 'nullable|string',
            'price'        => 'required|numeric|min:0',
            'image'        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'is_available' => 'boolean',
        ]);

        if ($request->hasFile('image')) {
            if ($product->image) Storage::disk('public')->delete($product->image);
            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        $product->update($validated);

        return redirect()->route('admin.products.index')
            ->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product)
    {
        if ($product->image) Storage::disk('public')->delete($product->image);
        $product->delete();

        return redirect()->route('admin.products.index')
            ->with('success', 'Product deleted successfully.');
    }
}