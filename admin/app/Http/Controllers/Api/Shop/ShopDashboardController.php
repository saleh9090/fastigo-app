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
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

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
        [$startDate, $endDate, $periodLabel] = $this->dashboardDateRange($request);
        $branchId = $this->dashboardBranchId($request, $user);

        $periodBillsQuery = Bill::query()
            ->where('company_id', $companyId)
            ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId))
            ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()]);

        $periodSales = (float) (clone $periodBillsQuery)->sum('total_amount');
        $periodBillCount = (clone $periodBillsQuery)->count();
        $periodExpenses = (float) Expense::query()
            ->where('company_id', $companyId)
            ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId))
            ->whereBetween('expense_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->sum('amount');
        $periodAverageOrder = $periodBillCount > 0 ? round($periodSales / $periodBillCount, 3) : 0;
        $previousRangeDays = $startDate->diffInDays($endDate) + 1;
        $previousStartDate = $startDate->copy()->subDays($previousRangeDays);
        $previousEndDate = $startDate->copy()->subDay();
        $previousBillsQuery = Bill::query()
            ->where('company_id', $companyId)
            ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId))
            ->whereBetween('created_at', [$previousStartDate->copy()->startOfDay(), $previousEndDate->copy()->endOfDay()]);
        $previousSales = (float) (clone $previousBillsQuery)->sum('total_amount');
        $previousBillCount = (clone $previousBillsQuery)->count();
        $previousAverageOrder = $previousBillCount > 0 ? round($previousSales / $previousBillCount, 3) : 0;

        $latestBills = Bill::query()
            ->where('company_id', $companyId)
            ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId))
            ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
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
            'period' => $request->string('period', 'today')->toString(),
            'period_label' => $periodLabel,
            'date_from' => $startDate->toDateString(),
            'date_to' => $endDate->toDateString(),
            'selected_branch_id' => $branchId,
            'today_sales' => $periodSales,
            'today_expenses' => $periodExpenses,
            'today_net_profit' => round($periodSales - $periodExpenses, 3),
            'total_bills_today' => $periodBillCount,
            'bills_in_process' => (clone $periodBillsQuery)->where('status', 'in_process')->count(),
            'bills_ready' => (clone $periodBillsQuery)->where('status', 'ready')->count(),
            'bills_delivered' => (clone $periodBillsQuery)->where('status', 'delivered')->count(),
            'dashboard_summary' => [
                'orders' => [
                    'value' => $periodBillCount,
                    'change' => $this->percentageChange($periodBillCount, $previousBillCount),
                ],
                'net_sales' => [
                    'value' => round($periodSales, 3),
                    'change' => $this->percentageChange($periodSales, $previousSales),
                ],
                'average_order' => [
                    'value' => $periodAverageOrder,
                    'change' => $this->percentageChange($periodAverageOrder, $previousAverageOrder),
                ],
            ],
            'gross_sales_chart' => $this->grossSalesChart($companyId, $branchId, $startDate, $endDate),
            'total_customers' => Bill::query()
                ->where('company_id', $companyId)
                ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId))
                ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
                ->distinct()
                ->count('customer_phone'),
            'total_products' => Product::query()
                ->where('company_id', $companyId)
                ->count(),
            'latest_bills' => $latestBills,
        ]);
    }

    private function percentageChange(float|int $current, float|int $previous): float
    {
        if ((float) $previous === 0.0) {
            return (float) $current === 0.0 ? 0.0 : 100.0;
        }

        return round(((float) $current - (float) $previous) / (float) $previous * 100, 1);
    }

    private function grossSalesChart(int $companyId, ?int $branchId, Carbon $startDate, Carbon $endDate): array
    {
        $dailySales = Bill::query()
            ->selectRaw('DATE(created_at) as sale_date, SUM(total_amount) as total')
            ->where('company_id', $companyId)
            ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId))
            ->whereBetween('created_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
            ->groupBy('sale_date')
            ->pluck('total', 'sale_date');

        $points = [];
        $cursor = $startDate->copy()->startOfDay();
        $end = $endDate->copy()->startOfDay();

        while ($cursor->lte($end)) {
            $date = $cursor->toDateString();
            $points[] = [
                'label' => $cursor->format('j'),
                'date' => $date,
                'value' => round((float) ($dailySales[$date] ?? 0), 3),
            ];
            $cursor->addDay();
        }

        return [
            'total' => round(array_sum(array_column($points, 'value')), 3),
            'points' => $points,
        ];
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

    private function dashboardBranchId(Request $request, User $user): ?int
    {
        if ($this->isBranchEmployee($user)) {
            return $user->branch_id;
        }

        $branchId = $request->integer('branch_id');

        if (! $branchId) {
            return null;
        }

        $branchExists = $user->company
            ? $user->company->branches()->whereKey($branchId)->exists()
            : false;

        if (! $branchExists) {
            throw ValidationException::withMessages([
                'branch_id' => 'The selected branch does not belong to this company.',
            ]);
        }

        return $branchId;
    }

    private function dashboardDateRange(Request $request): array
    {
        $period = $request->string('period', 'today')->toString();
        $today = today();
        $anchorDate = $request->date('date') ?? $today;

        return match ($period) {
            'day' => [
                $anchorDate->copy(),
                $anchorDate->copy(),
                'Day',
            ],
            'yesterday' => [
                $today->copy()->subDay(),
                $today->copy()->subDay(),
                'Yesterday',
            ],
            'this_week' => [
                $anchorDate->copy()->startOfWeek(),
                $anchorDate->copy()->endOfWeek(),
                'This week',
            ],
            'this_month' => [
                $anchorDate->copy()->startOfMonth(),
                $anchorDate->copy()->endOfMonth(),
                'This month',
            ],
            'this_year' => [
                $anchorDate->copy()->startOfYear(),
                $anchorDate->copy()->endOfYear(),
                'This year',
            ],
            'last_month' => [
                $today->copy()->subMonthNoOverflow()->startOfMonth(),
                $today->copy()->subMonthNoOverflow()->endOfMonth(),
                'Last month',
            ],
            'custom' => $this->customDashboardDateRange($request),
            default => [
                $today,
                $today,
                'Today',
            ],
        };
    }

    private function customDashboardDateRange(Request $request): array
    {
        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        return [
            Carbon::parse($validated['start_date']),
            Carbon::parse($validated['end_date']),
            'Custom dates',
        ];
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
