<?php

namespace App\Orders\Repositories;

use App\Orders\Models\Order;
use Illuminate\Database\Eloquent\Collection;

class OrderRepository
{
    public function findByCustomerId(int $customerId, ?int $orderId = null): Collection
    {
        return Order::with(['customer', 'status', 'products', 'paymentPlans.payments'])
            ->where('customer_id', $customerId)
            ->when($orderId, fn($query) => $query->where('id', $orderId))
            ->get();
    }

    public function findById(int $id): ?Order
    {
        return Order::find($id);
    }

    public function findByUser(int $userId, int $orderId): ?Order
    {
        return Order::where('customer_id', $userId)
            ->where('id', $orderId)
            ->first();
    }

    public function create(array $data): Order
    {
        return Order::create($data);
    }

    public function updateStatus(int $orderId, array $data): ?int
    {
        return Order::where('id', $orderId)->update($data);
    }

}
