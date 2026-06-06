<?php

namespace App\Http\Controllers\Api\Shop;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\BillItem;
use App\Models\Branch;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShopReportController extends Controller
{
    public function sales(Request $request)
    {
        $user = $this->businessUser($request);

        if (! $user) {
            return $this->forbiddenResponse();
        }

        $dateFilters = $this->dateFilters($request);
        $billQuery = $this->billQuery($user, $dateFilters);

        return response()->json([
            'total_sales' => round((float) (clone $billQuery)->sum('total_amount'), 3),
            'sales_by_date' => $this->salesByDate(clone $billQuery),
            'sales_by_branch' => $this->salesByBranch(clone $billQuery),
            'sales_by_employee' => $this->salesByEmployee(clone $billQuery),
            'bills_by_status' => $this->billsByStatus(clone $billQuery),
            'top_products' => $this->topProducts($user, $dateFilters),
        ]);
    }

    public function expenses(Request $request)
    {
        $user = $this->businessUser($request);

        if (! $user) {
            return $this->forbiddenResponse();
        }

        $expenseQuery = $this->expenseQuery($user, $this->dateFilters($request));

        return response()->json([
            'total_expenses' => round((float) (clone $expenseQuery)->sum('amount'), 3),
            'expenses_by_date' => $this->expensesByDate(clone $expenseQuery),
            'expenses_by_branch' => $this->expensesByBranch(clone $expenseQuery),
            'expenses_by_employee' => $this->expensesByEmployee(clone $expenseQuery),
        ]);
    }

    public function profit(Request $request)
    {
        $user = $this->businessUser($request);

        if (! $user) {
            return $this->forbiddenResponse();
        }

        $dateFilters = $this->dateFilters($request);
        $totalSales = (float) $this->billQuery($user, $dateFilters)->sum('total_amount');
        $totalExpenses = (float) $this->expenseQuery($user, $dateFilters)->sum('amount');

        return response()->json([
            'total_sales' => round($totalSales, 3),
            'total_expenses' => round($totalExpenses, 3),
            'net_profit' => round($totalSales - $totalExpenses, 3),
        ]);
    }

    public function branches(Request $request)
    {
        $user = $this->businessUser($request);

        if (! $user) {
            return $this->forbiddenResponse();
        }

        $dateFilters = $this->dateFilters($request);
        $branches = Branch::query()
            ->where('company_id', $user->company_id)
            ->when($user->role === 'branch_employee', fn (Builder $query) => $query->whereKey($user->branch_id))
            ->orderBy('name')
            ->get();

        $data = $branches->map(function (Branch $branch) use ($user, $dateFilters): array {
            $sales = (float) $this->billQuery($user, $dateFilters)
                ->where('branch_id', $branch->id)
                ->sum('total_amount');
            $expenses = (float) $this->expenseQuery($user, $dateFilters)
                ->where('branch_id', $branch->id)
                ->sum('amount');

            return [
                'branch_id' => $branch->id,
                'branch_name' => $branch->name,
                'total_sales' => round($sales, 3),
                'total_expenses' => round($expenses, 3),
                'net_profit' => round($sales - $expenses, 3),
            ];
        });

        return response()->json([
            'branches' => $data,
        ]);
    }

    private function businessUser(Request $request): ?User
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->company_id) {
            return null;
        }

        if (! in_array($user->role, ['company_manager', 'branch_employee'], true)) {
            return null;
        }

        if ($user->role === 'branch_employee' && ! $user->branch_id) {
            return null;
        }

        return $user;
    }

    /**
     * @return array{start_date: string|null, end_date: string|null}
     */
    private function dateFilters(Request $request): array
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ]);

        return [
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
        ];
    }

    /**
     * @param array{start_date: string|null, end_date: string|null} $dateFilters
     */
    private function billQuery(User $user, array $dateFilters): Builder
    {
        return Bill::query()
            ->where('bills.company_id', $user->company_id)
            ->when($user->role === 'branch_employee', fn (Builder $query) => $query->where('bills.branch_id', $user->branch_id))
            ->when($dateFilters['start_date'], fn (Builder $query, string $date) => $query->whereDate('bills.created_at', '>=', $date))
            ->when($dateFilters['end_date'], fn (Builder $query, string $date) => $query->whereDate('bills.created_at', '<=', $date));
    }

    /**
     * @param array{start_date: string|null, end_date: string|null} $dateFilters
     */
    private function expenseQuery(User $user, array $dateFilters): Builder
    {
        return Expense::query()
            ->where('expenses.company_id', $user->company_id)
            ->when($user->role === 'branch_employee', fn (Builder $query) => $query->where('expenses.branch_id', $user->branch_id))
            ->when($dateFilters['start_date'], fn (Builder $query, string $date) => $query->whereDate('expenses.expense_date', '>=', $date))
            ->when($dateFilters['end_date'], fn (Builder $query, string $date) => $query->whereDate('expenses.expense_date', '<=', $date));
    }

    private function salesByDate(Builder $query): array
    {
        return $query
            ->selectRaw('DATE(created_at) as date')
            ->selectRaw('SUM(total_amount) as total')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get()
            ->map(fn ($row): array => [
                'date' => $row->date,
                'total' => round((float) $row->total, 3),
            ])
            ->all();
    }

    private function expensesByDate(Builder $query): array
    {
        return $query
            ->select('expense_date as date')
            ->selectRaw('SUM(amount) as total')
            ->groupBy('expense_date')
            ->orderBy('expense_date')
            ->get()
            ->map(fn ($row): array => [
                'date' => (string) $row->date,
                'total' => round((float) $row->total, 3),
            ])
            ->all();
    }

    private function salesByBranch(Builder $query): array
    {
        return $query
            ->leftJoin('branches', 'bills.branch_id', '=', 'branches.id')
            ->select('bills.branch_id')
            ->selectRaw('COALESCE(branches.name, "Unassigned") as branch_name')
            ->selectRaw('SUM(bills.total_amount) as total')
            ->groupBy('bills.branch_id', 'branches.name')
            ->orderBy('branch_name')
            ->get()
            ->map(fn ($row): array => [
                'branch_id' => $row->branch_id,
                'branch_name' => $row->branch_name,
                'total' => round((float) $row->total, 3),
            ])
            ->all();
    }

    private function expensesByBranch(Builder $query): array
    {
        return $query
            ->leftJoin('branches', 'expenses.branch_id', '=', 'branches.id')
            ->select('expenses.branch_id')
            ->selectRaw('COALESCE(branches.name, "Unassigned") as branch_name')
            ->selectRaw('SUM(expenses.amount) as total')
            ->groupBy('expenses.branch_id', 'branches.name')
            ->orderBy('branch_name')
            ->get()
            ->map(fn ($row): array => [
                'branch_id' => $row->branch_id,
                'branch_name' => $row->branch_name,
                'total' => round((float) $row->total, 3),
            ])
            ->all();
    }

    private function salesByEmployee(Builder $query): array
    {
        return $query
            ->leftJoin('users', 'bills.created_by', '=', 'users.id')
            ->select('bills.created_by')
            ->selectRaw('COALESCE(users.name, "Unknown") as employee_name')
            ->selectRaw('SUM(bills.total_amount) as total')
            ->groupBy('bills.created_by', 'users.name')
            ->orderBy('employee_name')
            ->get()
            ->map(fn ($row): array => [
                'user_id' => $row->created_by,
                'employee_name' => $row->employee_name,
                'total' => round((float) $row->total, 3),
            ])
            ->all();
    }

    private function expensesByEmployee(Builder $query): array
    {
        return $query
            ->leftJoin('users', 'expenses.created_by', '=', 'users.id')
            ->select('expenses.created_by')
            ->selectRaw('COALESCE(users.name, "Unknown") as employee_name')
            ->selectRaw('SUM(expenses.amount) as total')
            ->groupBy('expenses.created_by', 'users.name')
            ->orderBy('employee_name')
            ->get()
            ->map(fn ($row): array => [
                'user_id' => $row->created_by,
                'employee_name' => $row->employee_name,
                'total' => round((float) $row->total, 3),
            ])
            ->all();
    }

    private function billsByStatus(Builder $query): array
    {
        return $query
            ->select('status')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('status')
            ->orderBy('status')
            ->pluck('total', 'status')
            ->map(fn ($total): int => (int) $total)
            ->all();
    }

    /**
     * @param array{start_date: string|null, end_date: string|null} $dateFilters
     */
    private function topProducts(User $user, array $dateFilters): array
    {
        return BillItem::query()
            ->join('bills', 'bill_items.bill_id', '=', 'bills.id')
            ->leftJoin('products', 'bill_items.product_id', '=', 'products.id')
            ->where('bills.company_id', $user->company_id)
            ->when($user->role === 'branch_employee', fn (Builder $query) => $query->where('bills.branch_id', $user->branch_id))
            ->when($dateFilters['start_date'], fn (Builder $query, string $date) => $query->whereDate('bills.created_at', '>=', $date))
            ->when($dateFilters['end_date'], fn (Builder $query, string $date) => $query->whereDate('bills.created_at', '<=', $date))
            ->select('bill_items.product_id')
            ->selectRaw('COALESCE(products.name, bill_items.description, "Unknown") as product_name')
            ->selectRaw('SUM(bill_items.quantity) as quantity')
            ->selectRaw('SUM(bill_items.total) as total')
            ->groupBy('bill_items.product_id', 'products.name', 'bill_items.description')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($row): array => [
                'product_id' => $row->product_id,
                'product_name' => $row->product_name,
                'quantity' => round((float) $row->quantity, 3),
                'total' => round((float) $row->total, 3),
            ])
            ->all();
    }

    private function forbiddenResponse()
    {
        return response()->json([
            'message' => 'Authenticated user is not allowed to access business reports.',
        ], 403);
    }
}
