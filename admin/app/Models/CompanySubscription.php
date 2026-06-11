<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class CompanySubscription extends Model
{
    protected $fillable = [
        'company_id',
        'subscription_package_id',
        'subscription_start',
        'subscription_end',
        'plan_name',
        'starts_at',
        'ends_at',
    ];

    protected static function booted(): void
    {
        static::saving(function (CompanySubscription $subscription): void {
            if (Schema::hasColumn($subscription->getTable(), 'plan_name') && blank($subscription->plan_name)) {
                $subscription->plan_name = SubscriptionPackage::query()
                    ->whereKey($subscription->subscription_package_id)
                    ->value('name') ?? 'Subscription';
            }

            if (Schema::hasColumn($subscription->getTable(), 'starts_at') && blank($subscription->starts_at)) {
                $subscription->starts_at = $subscription->subscription_start ?? now()->toDateString();
            }

            if (Schema::hasColumn($subscription->getTable(), 'ends_at') && blank($subscription->ends_at)) {
                $subscription->ends_at = $subscription->subscription_end;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'subscription_start' => 'date',
            'subscription_end' => 'date',
            'starts_at' => 'date',
            'ends_at' => 'date',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function subscriptionPackage()
    {
        return $this->belongsTo(SubscriptionPackage::class);
    }

    public function scopeCurrentFirst(Builder $query): Builder
    {
        return $query
            ->orderByRaw('subscription_end IS NULL DESC')
            ->orderByDesc('subscription_end')
            ->orderByDesc('id');
    }
}
