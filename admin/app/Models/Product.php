<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'company_id',
        'category_id',
        'unit_id',
        'name',
        'type',
        'description',
        'price',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:3',
            'active' => 'boolean',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function billItems()
    {
        return $this->hasMany(BillItem::class);
    }
}
