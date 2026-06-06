<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bill extends Model
{
    protected $fillable = [
        'company_id',
        'branch_id',
        'customer_id',
        'bill_number',
        'customer_phone',
        'total_amount',
        'paid_amount',
        'remaining_amount',
        'payment_status',
        'payment_method',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:3',
            'paid_amount' => 'decimal:3',
            'remaining_amount' => 'decimal:3',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function billItems()
    {
        return $this->hasMany(BillItem::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}
