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

        $company = Company::with('currentSubscription.subscriptionPackage')->findOrFail($user->company_id);

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

    public function users(Request $request)
    {
        return $this->businessUsersResponse($request);
    }

    public function employees(Request $request)
    {
        return $this->businessUsersResponse($request);
    }

    public function businessUsersResponse(Request $request)
    {
        $user = $this->businessUser($request);

        if (! $this->isCompanyManager($user)) {
            return $this->forbiddenResponse('Only company managers can access users.');
        }

        $users = User::query()
            ->with('branch')
            ->where('company_id', $user->company_id)
            ->whereIn('role', ['company_manager', 'branch_employee'])
            ->orderBy('name')
            ->get()
            ->map(fn (User $businessUser): array => $this->formatBusinessUser($businessUser));

        return response()->json([
            'users' => $users,
            'employees' => $users,
        ]);
    }

    public function storeUser(Request $request)
    {
        return $this->storeBusinessUser($request);
    }

    public function storeEmployee(Request $request)
    {
        return $this->storeBusinessUser($request);
    }

    public function storeBusinessUser(Request $request)
    {
        $user = $this->businessUser($request);

        if (! $this->isCompanyManager($user)) {
            return $this->forbiddenResponse('Only company managers can create users.');
        }

        $company = Company::with('currentSubscription.subscriptionPackage')->findOrFail($user->company_id);

        if (! $company->canAddUser()) {
            throw ValidationException::withMessages([
                'email' => 'This company has reached its subscription user limit.',
            ]);
        }

        $validated = $this->validateBusinessUser($request, $user);

        $businessUser = User::create([
            ...$validated,
            'company_id' => $user->company_id,
            'password' => Hash::make($validated['password']),
            'active' => $validated['active'] ?? true,
        ]);

        $businessUser->load('branch');

        return response()->json([
            'user' => $this->formatBusinessUser($businessUser),
            'employee' => $this->formatBusinessUser($businessUser),
        ], 201);
    }

    public function updateUser(Request $request, User $user)
    {
        return $this->updateBusinessUser($request, $user);
    }

    public function updateEmployee(Request $request, User $user)
    {
        return $this->updateBusinessUser($request, $user);
    }

    public function updateBusinessUser(Request $request, User $user)
    {
        $manager = $this->businessUser($request);

        if (
            ! $this->isCompanyManager($manager) ||
            (int) $user->company_id !== (int) $manager->company_id ||
            ! in_array($user->role, ['company_manager', 'branch_employee'], true)
        ) {
            return $this->forbiddenResponse('Only company managers can update company users.');
        }

        $company = Company::with('currentSubscription.subscriptionPackage')->findOrFail($manager->company_id);

        if (! $company->canAddUser($user->id)) {
            throw ValidationException::withMessages([
                'email' => 'This company has reached its subscription user limit.',
            ]);
        }

        $validated = $this->validateBusinessUser($request, $manager, $user);
        $password = $validated['password'] ?? null;
        unset($validated['password']);

        if ($password) {
            $validated['password'] = Hash::make($password);
        }

        $user->update($validated);
        $user->load('branch');

        return response()->json([
            'user' => $this->formatBusinessUser($user),
            'employee' => $this->formatBusinessUser($user),
        ]);
    }

    public function destroyUser(Request $request, User $user)
    {
        $manager = $this->businessUser($request);

        if (
            ! $this->isCompanyManager($manager) ||
            (int) $user->company_id !== (int) $manager->company_id ||
            ! in_array($user->role, ['company_manager', 'branch_employee'], true)
        ) {
            return $this->forbiddenResponse('Only company managers can delete company users.');
        }

        if ((int) $manager->id === (int) $user->id) {
            throw ValidationException::withMessages([
                'user' => 'You cannot delete your own user account.',
            ]);
        }

        $user->delete();

        return response()->json([
            'message' => 'User deleted.',
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

    private function validateBusinessUser(Request $request, User $manager, ?User $user = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user?->id),
            ],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8'],
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

    private function formatBusinessUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role,
            'branch_id' => $user->branch_id,
            'branch_name' => $user->branch?->name,
            'active' => $user->active,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];
    }

    private function forbiddenResponse(string $message)
    {
        return response()->json([
            'message' => $message,
        ], 403);
    }
}
