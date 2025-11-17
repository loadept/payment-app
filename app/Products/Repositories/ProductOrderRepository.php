<?php

namespace App\Products\Repositories;

use App\Products\Models\ProductOrder;

class ProductOrderRepository
{
    public function insertBatch(array $productOrders): void
    {
        ProductOrder::insert($productOrders);
    }
}
