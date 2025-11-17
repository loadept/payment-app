<?php

namespace App\Payments\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentPlan extends Model
{
    use HasFactory;

    protected $table = 'payment_plan';

    protected $fillable = [
        'order_id',
        'installment_number',
        'amount',
        'is_paid',
        'due_date',
    ];

    protected static function newFactory()
    {
        return \Database\Factories\PaymentPlanFactory::new();
    }

    public function order()
    {
        return $this->belongsTo(\App\Orders\Models\Order::class, 'order_id');
    }

    public function payments()
    {
        return $this->hasMany(\App\Payments\Models\Payment::class, 'payment_plan_id');
    }
}
