<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\SalesReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SalesReportController extends Controller
{
    public function index()
    {
        $reports = SalesReport::with('generatedBy')->latest()->paginate(15);
        return view('reports.index', compact('reports'));
    }

    public function generate(Request $request)
    {
        $validated = $request->validate([
            'period'      => 'required|in:daily,weekly,monthly',
            'report_date' => 'required|date',
        ]);

        [$start, $end] = $this->getDateRange(
            $validated['period'],
            $validated['report_date']
        );

        $orders = Order::whereBetween('created_at', [$start, $end])
            ->where('status', 'completed')
            ->get();

        $totalRevenue  = $orders->sum('total');
        $totalDiscount = $orders->sum('discount');
        $totalItems    = OrderItem::whereIn('order_id', $orders->pluck('id'))->sum('quantity');

        $report = SalesReport::create([
            'report_date'      => $validated['report_date'],
            'period'           => $validated['period'],
            'total_orders'     => $orders->count(),
            'total_revenue'    => $totalRevenue,
            'total_discount'   => $totalDiscount,
            'total_items_sold' => $totalItems,
            'generated_by'     => Auth::id(),
        ]);

        return redirect()->route('reports.show', $report)
            ->with('success', 'Report generated successfully.');
    }

    public function show(SalesReport $report)
    {
        $report->load('generatedBy');
        return view('reports.show', compact('report'));
    }

    public function destroy(SalesReport $report)
    {
        $report->delete();

        return redirect()->route('reports.index')
            ->with('success', 'Report deleted.');
    }

    // ── Private helpers ────────────────────────────────────

    private function getDateRange(string $period, string $date): array
    {
        $carbon = \Carbon\Carbon::parse($date);

        return match ($period) {
            'daily'   => [$carbon->startOfDay(), $carbon->copy()->endOfDay()],
            'weekly'  => [$carbon->startOfWeek(), $carbon->copy()->endOfWeek()],
            'monthly' => [$carbon->startOfMonth(), $carbon->copy()->endOfMonth()],
        };
    }
}