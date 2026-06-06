<?php

namespace App\Http\Controllers\Api\Shop;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Expense;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;

class ShopDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $this->shopUser($request);

        if (! $user || ! $user->company_id) {
            return response()->json([
                'message' => 'Authenticated user is not allowed to access the shop dashboard.',
            ], 403);
        }

        $companyId = $user->company_id;
        $today = today()->toDateString();

        $todayBillsQuery = Bill::query()
            ->where('company_id', $companyId)
            ->whereDate('created_at', $today);

        $todaySales = (float) (clone $todayBillsQuery)->sum('total_amount');
        $todayExpenses = (float) Expense::query()
            ->where('company_id', $companyId)
            ->whereDate('expense_date', $today)
            ->sum('amount');

        $latestBills = Bill::query()
            ->where('company_id', $companyId)
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (Bill $bill): array => [
                'bill_number' => $bill->bill_number,
                'customer_phone' => $bill->customer_phone,
                'total_amount' => $bill->total_amount,
                'status' => $bill->status,
                'created_at' => $bill->created_at,
            ]);

        return response()->json([
            'today_sales' => $todaySales,
            'today_expenses' => $todayExpenses,
            'today_net_profit' => round($todaySales - $todayExpenses, 3),
            'total_bills_today' => (clone $todayBillsQuery)->count(),
            'bills_in_process' => (clone $todayBillsQuery)->where('status', 'in_process')->count(),
            'bills_ready' => (clone $todayBillsQuery)->where('status', 'ready')->count(),
            'bills_delivered' => (clone $todayBillsQuery)->where('status', 'delivered')->count(),
            'total_customers' => Bill::query()
                ->where('company_id', $companyId)
                ->distinct()
                ->count('customer_phone'),
            'total_products' => Product::query()
                ->where('company_id', $companyId)
                ->count(),
            'latest_bills' => $latestBills,
        ]);
    }

    public function customers()
    {
        return response()->json(['message' => 'Not implemented.'], 501);
    }

    private function shopUser(Request $request): ?User
    {
        $user = $request->user();

        return $user instanceof User ? $user : null;
    }
}
