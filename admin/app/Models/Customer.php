<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class Customer extends Model
{
    use HasApiTokens;

    protected $fillable = [
        'name',
        'country_code',
        'phone',
        'email',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public function bills()
    {
        return $this->hasMany(Bill::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function getFullPhoneAttribute(): string
    {
        $countryCode = '+' . preg_replace('/\D+/', '', $this->country_code ?? '+968');
        $phone = preg_replace('/\D+/', '', $this->phone ?? '');

        return $countryCode . $phone;
    }
}
