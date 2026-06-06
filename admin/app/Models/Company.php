<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = [
        'subscription_package_id',
        'name',
        'commercial_registration',
        'contact_person',
        'phone',
        'email',
        'address',
        'subscription_start',
        'subscription_end',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'subscription_start' => 'date',
            'subscription_end' => 'date',
        ];
    }

    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    public function subscriptionPackage()
    {
        return $this->belongsTo(SubscriptionPackage::class);
    }

    public function canCreateBills(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->subscription_end && $this->subscription_end->isBefore(today())) {
            return false;
        }

        if ($this->subscriptionPackage && $this->subscriptionPackage->status !== 'active') {
            return false;
        }

        return true;
    }

    public function canAddBranch(?int $ignoreBranchId = null): bool
    {
        if (! $this->subscriptionPackage) {
            return true;
        }

        $branchCount = $this->branches()
            ->when($ignoreBranchId, fn ($query) => $query->whereKeyNot($ignoreBranchId))
            ->count();

        return $branchCount < $this->subscriptionPackage->max_branches;
    }

    public function canAddEmployee(?int $ignoreUserId = null): bool
    {
        if (! $this->subscriptionPackage) {
            return true;
        }

        $employeeCount = $this->users()
            ->whereIn('role', ['company_manager', 'branch_employee'])
            ->when($ignoreUserId, fn ($query) => $query->whereKeyNot($ignoreUserId))
            ->count();

        return $employeeCount < $this->subscriptionPackage->max_employees;
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function categories()
    {
        return $this->productCategories();
    }

    public function productCategories()
    {
        return $this->hasMany(ProductCategory::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function bills()
    {
        return $this->hasMany(Bill::class);
    }

    public function expenseCategories()
    {
        return $this->hasMany(ExpenseCategory::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }
}
