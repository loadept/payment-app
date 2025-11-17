<?php

namespace App\Orders\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 1;
    public const STATUS_PAID = 2;
    public const STATUS_CANCELLED = 3;
    public const STATUS_FAILED = 4;

    protected $table = 'order';

    protected $fillable = [
        'customer_id',
        'total_amount',
        'installments',
    ];

    protected static function newFactory()
    {
        return \Database\Factories\OrderFactory::new();
    }

    public function customer()
    {
        return $this->belongsTo(\App\Users\Models\User::class, 'customer_id');
    }

    public function status()
    {
        return $this->belongsTo(OrderStatus::class, 'order_status_id');
    }

    public function paymentPlans()
    {
        return $this->hasMany(\App\Payments\Models\PaymentPlan::class, 'order_id');
    }

    public function productOrders()
    {
        return $this->hasMany(\App\Products\Models\ProductOrder::class, 'order_id');
    }

    public function products()
    {
        return $this->belongsToMany(
            \App\Products\Models\Product::class,
            'product_order',
            'order_id',
            'product_id'
        )->withPivot('quantity');
    }
}
