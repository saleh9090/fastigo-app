<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = [
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

    public function branches()
    {
        return $this->hasMany(Branch::class);
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
