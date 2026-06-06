<?php

namespace App\Http\Controllers\Api\Shop;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ShopManagementController extends Controller
{
    public function branches(Request $request)
    {
        $user = $this->businessUser($request);

        if (! $user) {
            return $this->forbiddenResponse('Authenticated user is not allowed to access branches.');
        }

        $branches = Branch::query()
            ->where('company_id', $user->company_id)
            ->when($this->isBranchEmployee($user), fn ($query) => $query->whereKey($user->branch_id))
            ->orderBy('name')
            ->get()
            ->map(fn (Branch $branch): array => $this->formatBranch($branch));

        return response()->json([
            'branches' => $branches,
        ]);
    }

    public function storeBranch(Request $request)
    {
        $user = $this->businessUser($request);

        if (! $this->isCompanyManager($user)) {
            return $this->forbiddenResponse('Only company managers can create branches.');
        }

        $company = Company::with('subscriptionPackage')->findOrFail($user->company_id);

        if (! $company->canAddBranch()) {
            throw ValidationException::withMessages([
                'name' => 'This company has reached its subscription branch limit.',
            ]);
        }

        $branch = Branch::create([
            ...$this->validateBranch($request),
            'company_id' => $user->company_id,
        ]);

        return response()->json([
            'branch' => $this->formatBranch($branch),
        ], 201);
    }

    public function updateBranch(Request $request, Branch $branch)
    {
        $user = $this->businessUser($request);

        if (! $this->isCompanyManager($user) || (int) $branch->company_id !== (int) $user->company_id) {
            return $this->forbiddenResponse('Only company managers can update company branches.');
        }

        $branch->update($this->validateBranch($request));

        return response()->json([
            'branch' => $this->formatBranch($branch),
        ]);
    }

    public function employees(Request $request)
    {
        $user = $this->businessUser($request);

        if (! $this->isCompanyManager($user)) {
            return $this->forbiddenResponse('Only company managers can access employees.');
        }

        $employees = User::query()
            ->with('branch')
            ->where('company_id', $user->company_id)
            ->whereIn('role', ['company_manager', 'branch_employee'])
            ->orderBy('name')
            ->get()
            ->map(fn (User $employee): array => $this->formatEmployee($employee));

        return response()->json([
            'employees' => $employees,
        ]);
    }

    public function storeEmployee(Request $request)
    {
        $user = $this->businessUser($request);

        if (! $this->isCompanyManager($user)) {
            return $this->forbiddenResponse('Only company managers can create employees.');
        }

        $company = Company::with('subscriptionPackage')->findOrFail($user->company_id);

        if (! $company->canAddEmployee()) {
            throw ValidationException::withMessages([
                'email' => 'This company has reached its subscription employee limit.',
            ]);
        }

        $validated = $this->validateEmployee($request, $user);

        $employee = User::create([
            ...$validated,
            'company_id' => $user->company_id,
            'password' => Hash::make($validated['password']),
            'active' => $validated['active'] ?? true,
        ]);

        $employee->load('branch');

        return response()->json([
            'employee' => $this->formatEmployee($employee),
        ], 201);
    }

    public function updateEmployee(Request $request, User $employee)
    {
        $user = $this->businessUser($request);

        if (
            ! $this->isCompanyManager($user) ||
            (int) $employee->company_id !== (int) $user->company_id ||
            ! in_array($employee->role, ['company_manager', 'branch_employee'], true)
        ) {
            return $this->forbiddenResponse('Only company managers can update company employees.');
        }

        $company = Company::with('subscriptionPackage')->findOrFail($user->company_id);

        if (! $company->canAddEmployee($employee->id)) {
            throw ValidationException::withMessages([
                'email' => 'This company has reached its subscription employee limit.',
            ]);
        }

        $validated = $this->validateEmployee($request, $user, $employee);
        $password = $validated['password'] ?? null;
        unset($validated['password']);

        if ($password) {
            $validated['password'] = Hash::make($password);
        }

        $employee->update($validated);
        $employee->load('branch');

        return response()->json([
            'employee' => $this->formatEmployee($employee),
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

        return $user;
    }

    private function isCompanyManager(?User $user): bool
    {
        return $user instanceof User && $user->role === 'company_manager' && (bool) $user->company_id;
    }

    private function isBranchEmployee(User $user): bool
    {
        return $user->role === 'branch_employee';
    }

    private function validateBranch(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string'],
        ]);
    }

    private function validateEmployee(Request $request, User $manager, ?User $employee = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($employee?->id),
            ],
            'password' => [$employee ? 'nullable' : 'required', 'string', 'min:8'],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::in(['company_manager', 'branch_employee'])],
            'branch_id' => [
                'required_if:role,branch_employee',
                'nullable',
                'integer',
                Rule::exists('branches', 'id')->where('company_id', $manager->company_id),
            ],
            'active' => ['sometimes', 'boolean'],
        ]);
    }

    private function formatBranch(Branch $branch): array
    {
        return [
            'id' => $branch->id,
            'name' => $branch->name,
            'phone' => $branch->phone,
            'address' => $branch->address,
            'created_at' => $branch->created_at,
            'updated_at' => $branch->updated_at,
        ];
    }

    private function formatEmployee(User $employee): array
    {
        return [
            'id' => $employee->id,
            'name' => $employee->name,
            'email' => $employee->email,
            'phone' => $employee->phone,
            'role' => $employee->role,
            'branch_id' => $employee->branch_id,
            'branch_name' => $employee->branch?->name,
            'active' => $employee->active,
            'created_at' => $employee->created_at,
            'updated_at' => $employee->updated_at,
        ];
    }

    private function forbiddenResponse(string $message)
    {
        return response()->json([
            'message' => $message,
        ], 403);
    }
}
