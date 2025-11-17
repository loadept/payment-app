<?php

namespace App\Products\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductOrder extends Model
{
    use HasFactory;

    protected $table = 'product_order';

    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
    ];

    protected static function newFactory()
    {
        return \Database\Factories\ProductOrderFactory::new();
    }

    public function order()
    {
        return $this->belongsTo(\App\Orders\Models\Order::class, 'order_id');
    }
    public function product()
    {
        return $this->belongsTo(\App\Products\Models\Product::class, 'product_id');
    }
}
