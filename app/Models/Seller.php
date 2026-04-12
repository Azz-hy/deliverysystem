<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Seller extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'business_name', 'business_address'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    // Stats helpers
    public function totalOrders()
    {
        return $this->orders()->count();
    }

    public function deliveredOrders()
    {
        return $this->orders()->where('status', 'delivered')->count();
    }

    public function pendingOrders()
    {
        return $this->orders()->where('status', 'pending')->count();
    }

    public function failedOrders()
    {
        return $this->orders()->where('status', 'failed')->count();
    }

    public function totalSpent()
    {
        return $this->orders()->sum('delivery_fee');
    }

    public function thisMonthSpent()
    {
        return $this->orders()
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->sum('delivery_fee');
    }
}
