<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPackage extends Model
{
    protected $fillable = [
        'name',
        'monthly_price',
        'yearly_price',
        'max_branches',
        'max_users',
        'features',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'monthly_price' => 'decimal:3',
            'yearly_price' => 'decimal:3',
            'features' => 'array',
        ];
    }

    public function companySubscriptions()
    {
        return $this->hasMany(CompanySubscription::class);
    }
}
