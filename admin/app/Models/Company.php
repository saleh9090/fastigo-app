<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = [
        'name',
        'arabic_name',
        'commercial_registration',
        'contact_person',
        'phone',
        'email',
        'address',
        'status',
    ];

    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    public function companySubscriptions()
    {
        return $this->hasMany(CompanySubscription::class);
    }

    public function currentSubscription()
    {
        return $this->hasOne(CompanySubscription::class)
            ->ofMany([
                'subscription_end' => 'max',
                'id' => 'max',
            ]);
    }

    public function canCreateBills(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $subscription = $this->currentSubscription;

        if ($subscription?->subscription_end && $subscription->subscription_end->isBefore(today())) {
            return false;
        }

        if ($subscription?->subscriptionPackage && $subscription->subscriptionPackage->status !== 'active') {
            return false;
        }

        return true;
    }

    public function canAddBranch(?int $ignoreBranchId = null): bool
    {
        $subscriptionPackage = $this->currentSubscription?->subscriptionPackage;

        if (! $subscriptionPackage) {
            return true;
        }

        $branchCount = $this->branches()
            ->when($ignoreBranchId, fn ($query) => $query->whereKeyNot($ignoreBranchId))
            ->count();

        return $branchCount < $subscriptionPackage->max_branches;
    }

    public function canAddUser(?int $ignoreUserId = null): bool
    {
        $subscriptionPackage = $this->currentSubscription?->subscriptionPackage;

        if (! $subscriptionPackage) {
            return true;
        }

        $userCount = $this->users()
            ->whereIn('role', ['company_manager', 'branch_employee'])
            ->when($ignoreUserId, fn ($query) => $query->whereKeyNot($ignoreUserId))
            ->count();

        return $userCount < $subscriptionPackage->max_users;
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
