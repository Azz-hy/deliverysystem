<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'vehicle_type', 'vehicle_number', 'is_available'];

    protected $casts = ['is_available' => 'boolean'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    // Stats helpers
    public function activeOrdersCount()
    {
        return $this->orders()->whereNotIn('status', ['delivered', 'failed'])->count();
    }

    public function completedToday()
    {
        return $this->orders()->where('status', 'delivered')->whereDate('delivered_at', today())->count();
    }

    public function completedThisWeek()
    {
        return $this->orders()->where('status', 'delivered')->whereBetween('delivered_at', [now()->startOfWeek(), now()->endOfWeek()])->count();
    }

    public function totalCompleted()
    {
        return $this->orders()->where('status', 'delivered')->count();
    }

    public function successRate()
    {
        $total = $this->orders()->whereIn('status', ['delivered', 'failed'])->count();
        if ($total === 0) {
            return 0;
        }

        return round(($this->totalCompleted() / $total) * 100, 1);
    }
}
