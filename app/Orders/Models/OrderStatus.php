<?php

namespace App\Orders\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderStatus extends Model
{
    use HasFactory;

    protected $table = 'order_status';

    public $timestamps = false;

    protected $fillable = [
        'status',
    ];

    protected static function newFactory()
    {
        return \Database\Factories\OrderStatusFactory::new();
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'order_status_id', 'id');
    }
}
