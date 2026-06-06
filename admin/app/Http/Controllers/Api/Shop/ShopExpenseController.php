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

    public function update(Request $request, Expense $expense)
    {
        $user = $this->shopUser($request);

        if (! $this->canAccessExpense($expense, $user)) {
            return $this->forbiddenResponse();
        }

        $validated = $this->validateExpense($request, $user);

        if ($this->isBranchEmployee($user)) {
            if (! empty($validated['branch_id']) && (int) $validated['branch_id'] !== (int) $user->branch_id) {
                throw ValidationException::withMessages([
                    'branch_id' => 'Branch employees can update expenses only for their assigned branch.',
                ]);
            }

            $validated['branch_id'] = $user->branch_id;
        }

        $expense->update($validated);
        $expense->load(['branch', 'expenseCategory']);

        return response()->json([
            'expense' => $this->formatExpense($expense),
        ]);
    }

    public function destroy(Request $request, Expense $expense)
    {
        $user = $this->shopUser($request);

        if (! $this->canAccessExpense($expense, $user)) {
            return $this->forbiddenResponse();
        }

        $expense->delete();

        return response()->json([
            'message' => 'Expense deleted.',
        ]);
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

    public function storeCategory(Request $request)
    {
        $user = $this->shopUser($request);

        if (! $this->canManageExpenseCategories($user)) {
            return $this->forbiddenResponse();
        }

        $category = ExpenseCategory::create([
            ...$this->validateCategory($request),
            'company_id' => $user->company_id,
        ]);

        return response()->json([
            'expense_category' => $this->formatCategory($category),
        ], 201);
    }

    public function updateCategory(Request $request, ExpenseCategory $category)
    {
        $user = $this->shopUser($request);

        if (! $this->canManageExpenseCategories($user) || ! $this->belongsToCompany($category, $user)) {
            return $this->forbiddenResponse();
        }

        $category->update($this->validateCategory($request));

        return response()->json([
            'expense_category' => $this->formatCategory($category),
        ]);
    }

    public function destroyCategory(Request $request, ExpenseCategory $category)
    {
        $user = $this->shopUser($request);

        if (! $this->canManageExpenseCategories($user) || ! $this->belongsToCompany($category, $user)) {
            return $this->forbiddenResponse();
        }

        $category->delete();

        return response()->json([
            'message' => 'Expense category deleted.',
        ]);
    }

    private function formatExpense(Expense $expense): array
    {
        return [
            'id' => $expense->id,
            'branch_id' => $expense->branch_id,
            'expense_category_id' => $expense->expense_category_id,
            'branch_name' => $expense->branch?->name,
            'category_name' => $expense->expenseCategory?->name,
            'title' => $expense->title,
            'amount' => $expense->amount,
            'expense_date' => $expense->expense_date,
            'notes' => $expense->notes,
            'created_at' => $expense->created_at,
        ];
    }

    private function formatCategory(ExpenseCategory $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'description' => $category->description,
            'active' => $category->active,
        ];
    }

    private function validateExpense(Request $request, User $user): array
    {
        return $request->validate([
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
    }

    private function validateCategory(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'active' => ['sometimes', 'boolean'],
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

    private function canAccessExpense(Expense $expense, ?User $user): bool
    {
        if (! $user instanceof User || (int) $expense->company_id !== (int) $user->company_id) {
            return false;
        }

        if ($this->isBranchEmployee($user)) {
            return (int) $expense->branch_id === (int) $user->branch_id;
        }

        return $user->role === 'company_manager';
    }

    private function canManageExpenseCategories(?User $user): bool
    {
        return $user instanceof User
            && (bool) $user->company_id
            && $user->role === 'company_manager';
    }

    private function belongsToCompany(ExpenseCategory $category, User $user): bool
    {
        return (int) $category->company_id === (int) $user->company_id;
    }

    private function forbiddenResponse()
    {
        return response()->json([
            'message' => 'Authenticated user is not allowed to access shop expenses.',
        ], 403);
    }
}
