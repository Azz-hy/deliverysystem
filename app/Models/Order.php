<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_number', 'seller_id', 'driver_id',
        'customer_name', 'customer_phone', 'delivery_address',
        'product_description', 'product_price', 'delivery_fee', 'special_instructions',
        'delivery_zone', 'status', 'failure_reason', 'driver_notes',
        'assigned_at', 'picked_up_at', 'delivered_at',
    ];

    protected $casts = [
        'product_price' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'assigned_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';

    const STATUS_ASSIGNED = 'assigned';

    const STATUS_PICKED_UP = 'picked_up';

    const STATUS_ON_THE_WAY = 'on_the_way';

    const STATUS_DELIVERED = 'delivered';

    const STATUS_FAILED = 'failed';

    // Valid transitions map
    const TRANSITIONS = [
        'pending' => ['assigned', 'failed'],
        'assigned' => ['picked_up', 'failed'],
        'picked_up' => ['on_the_way', 'failed'],
        'on_the_way' => ['delivered', 'failed'],
        'delivered' => [],
        'failed' => [],
    ];

    public static function validTransitions(string $from): array
    {
        return self::TRANSITIONS[$from] ?? [];
    }

    public function canTransitionTo(string $newStatus): bool
    {
        return in_array($newStatus, self::validTransitions($this->status));
    }

    public function isEditable(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isFinal(): bool
    {
        return in_array($this->status, [self::STATUS_DELIVERED, self::STATUS_FAILED]);
    }

    // Status label helper
    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'Pending',
            'assigned' => 'Assigned',
            'picked_up' => 'Picked Up',
            'on_the_way' => 'On The Way',
            'delivered' => 'Delivered',
            'failed' => 'Failed',
            default => ucfirst($this->status),
        };
    }

    // Status badge color for UI
    public function statusColor(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'assigned' => 'info',
            'picked_up' => 'primary',
            'on_the_way' => 'secondary',
            'delivered' => 'success',
            'failed' => 'danger',
            default => 'secondary',
        };
    }

    // Relationships
    public function seller()
    {
        return $this->belongsTo(Seller::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    // Auto-generate order number
    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            $order->order_number = 'ORD-'.strtoupper(substr(uniqid(), -6)).'-'.date('Ymd');
        });
    }
}
