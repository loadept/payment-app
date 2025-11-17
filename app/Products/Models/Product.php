<?php

namespace App\Products\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $table = 'product';

    protected $fillable = [
        'name',
        'brand',
        'price',
    ];

    protected static function newFactory()
    {
        return \Database\Factories\ProductFactory::new();
    }

    public function productOrders()
    {
        return $this->hasMany(\App\Products\Models\ProductOrder::class, 'product_id');
    }
}
