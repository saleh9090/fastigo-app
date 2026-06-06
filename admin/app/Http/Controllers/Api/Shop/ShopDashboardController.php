<?php

namespace App\Http\Controllers\Api\Shop;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;

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
            ->when($this->isBranchEmployee($user), fn ($query) => $query->where('branch_id', $user->branch_id))
            ->whereDate('created_at', $today);

        $todaySales = (float) (clone $todayBillsQuery)->sum('total_amount');
        $todayExpenses = (float) Expense::query()
            ->where('company_id', $companyId)
            ->when($this->isBranchEmployee($user), fn ($query) => $query->where('branch_id', $user->branch_id))
            ->whereDate('expense_date', $today)
            ->sum('amount');

        $latestBills = Bill::query()
            ->where('company_id', $companyId)
            ->when($this->isBranchEmployee($user), fn ($query) => $query->where('branch_id', $user->branch_id))
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
                ->when($this->isBranchEmployee($user), fn ($query) => $query->where('branch_id', $user->branch_id))
                ->distinct()
                ->count('customer_phone'),
            'total_products' => Product::query()
                ->where('company_id', $companyId)
                ->count(),
            'latest_bills' => $latestBills,
        ]);
    }

    public function customers(Request $request)
    {
        $user = $this->shopUser($request);

        if (! $user || ! $user->company_id) {
            return response()->json([
                'message' => 'Authenticated user is not allowed to access shop customers.',
            ], 403);
        }

        $perPage = min((int) $request->integer('per_page', 15), 100);

        $customers = Customer::query()
            ->whereHas('bills', fn (Builder $query) => $this->scopeBillsToUser($query, $user))
            ->withCount(['bills' => fn (Builder $query) => $this->scopeBillsToUser($query, $user)])
            ->withMax(['bills' => fn (Builder $query) => $this->scopeBillsToUser($query, $user)], 'created_at')
            ->orderByDesc('bills_max_created_at')
            ->paginate($perPage)
            ->through(fn (Customer $customer): array => $this->formatCustomerSummary($customer));

        return response()->json($customers);
    }

    public function customer(Request $request, Customer $customer)
    {
        $user = $this->shopUser($request);

        if (! $user || ! $user->company_id) {
            return response()->json([
                'message' => 'Authenticated user is not allowed to access shop customers.',
            ], 403);
        }

        $bills = Bill::query()
            ->where('customer_id', $customer->id);

        $this->scopeBillsToUser($bills, $user);

        if (! $bills->exists()) {
            return response()->json([
                'message' => 'This customer is not connected to the authenticated company.',
            ], 403);
        }

        $billHistory = (clone $bills)
            ->with('branch')
            ->latest()
            ->get()
            ->map(fn (Bill $bill): array => [
                'id' => $bill->id,
                'bill_number' => $bill->bill_number,
                'branch_id' => $bill->branch_id,
                'branch_name' => $bill->branch?->name,
                'total_amount' => $bill->total_amount,
                'paid_amount' => $bill->paid_amount,
                'remaining_amount' => $bill->remaining_amount,
                'payment_status' => $bill->payment_status,
                'status' => $bill->status,
                'created_at' => $bill->created_at,
            ]);

        return response()->json([
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'country_code' => $customer->country_code,
                'phone' => $customer->phone,
                'full_phone' => $customer->full_phone,
                'email' => $customer->email,
                'active' => $customer->active,
                'bills_count' => $billHistory->count(),
                'total_sales' => round((float) (clone $bills)->sum('total_amount'), 3),
                'created_at' => $customer->created_at,
                'updated_at' => $customer->updated_at,
            ],
            'bills' => $billHistory,
        ]);
    }

    private function shopUser(Request $request): ?User
    {
        $user = $request->user();

        return $user instanceof User ? $user : null;
    }

    private function isBranchEmployee(User $user): bool
    {
        return $user->role === 'branch_employee';
    }

    private function scopeBillsToUser(Builder $query, User $user): Builder
    {
        return $query
            ->where('company_id', $user->company_id)
            ->when($this->isBranchEmployee($user), fn (Builder $query) => $query->where('branch_id', $user->branch_id));
    }

    private function formatCustomerSummary(Customer $customer): array
    {
        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'country_code' => $customer->country_code,
            'phone' => $customer->phone,
            'full_phone' => $customer->full_phone,
            'email' => $customer->email,
            'active' => $customer->active,
            'bills_count' => $customer->bills_count,
            'last_bill_at' => $customer->bills_max_created_at,
        ];
    }
}
