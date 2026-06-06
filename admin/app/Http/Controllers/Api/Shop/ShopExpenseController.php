<?php

namespace App\Http\Controllers\Api\Shop;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ShopExpenseController extends Controller
{
    public function index(Request $request)
    {
        $user = $this->shopUser($request);

        if (! $user || ! $user->company_id) {
            return $this->forbiddenResponse();
        }

        $expenses = Expense::query()
            ->with(['branch', 'expenseCategory'])
            ->where('company_id', $user->company_id)
            ->when($this->isBranchEmployee($user), fn ($query) => $query->where('branch_id', $user->branch_id))
            ->latest()
            ->get()
            ->map(fn (Expense $expense): array => $this->formatExpense($expense));

        return response()->json([
            'expenses' => $expenses,
        ]);
    }

    public function store(Request $request)
    {
        $user = $this->shopUser($request);

        if (! $user || ! $user->company_id) {
            return $this->forbiddenResponse();
        }

        if ($this->isBranchEmployee($user) && ! $user->branch_id) {
            return $this->forbiddenResponse();
        }

        $validated = $request->validate([
            'branch_id' => [
                'nullable',
                'integer',
                Rule::exists('branches', 'id')->where('company_id', $user->company_id),
            ],
            'expense_category_id' => [
                'nullable',
                'integer',
                Rule::exists('expense_categories', 'id')->where('company_id', $user->company_id),
            ],
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'expense_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($this->isBranchEmployee($user)) {
            if (! empty($validated['branch_id']) && (int) $validated['branch_id'] !== (int) $user->branch_id) {
                throw ValidationException::withMessages([
                    'branch_id' => 'Branch employees can create expenses only for their assigned branch.',
                ]);
            }

            $validated['branch_id'] = $user->branch_id;
        }

        $expense = Expense::create([
            'company_id' => $user->company_id,
            'branch_id' => $validated['branch_id'] ?? null,
            'expense_category_id' => $validated['expense_category_id'] ?? null,
            'title' => $validated['title'],
            'amount' => $validated['amount'],
            'expense_date' => $validated['expense_date'],
            'notes' => $validated['notes'] ?? null,
            'created_by' => $user->id,
        ]);

        $expense->load(['branch', 'expenseCategory']);

        return response()->json([
            'expense' => $this->formatExpense($expense),
        ], 201);
    }

    public function categories(Request $request)
    {
        $user = $this->shopUser($request);

        if (! $user || ! $user->company_id) {
            return $this->forbiddenResponse();
        }

        $categories = ExpenseCategory::query()
            ->where('company_id', $user->company_id)
            ->where('active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (ExpenseCategory $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'description' => $category->description,
                'active' => $category->active,
            ]);

        return response()->json([
            'expense_categories' => $categories,
        ]);
    }

    private function formatExpense(Expense $expense): array
    {
        return [
            'id' => $expense->id,
            'branch_name' => $expense->branch?->name,
            'category_name' => $expense->expenseCategory?->name,
            'title' => $expense->title,
            'amount' => $expense->amount,
            'expense_date' => $expense->expense_date,
            'notes' => $expense->notes,
            'created_at' => $expense->created_at,
        ];
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

    private function forbiddenResponse()
    {
        return response()->json([
            'message' => 'Authenticated user is not allowed to access shop expenses.',
        ], 403);
    }
}
