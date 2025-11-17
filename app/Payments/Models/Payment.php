<?php

namespace App\Payments\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $table = 'payment';

    protected $fillable = [
        'payment_plan_id',
        'amount',
        'transaction_id',
        'is_success',
    ];

    protected static function newFactory()
    {
        return \Database\Factories\PaymentFactory::new();
    }

    public function paymentPlan()
    {
        return $this->belongsTo(\App\Payments\Models\PaymentPlan::class, 'payment_plan_id');
    }
}
