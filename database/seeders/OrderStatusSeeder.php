<?php

namespace Database\Seeders;

use App\Orders\Models\Order;
use App\Orders\Models\OrderStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OrderStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        OrderStatus::insert([
            ['id' => Order::STATUS_PENDING, 'status' => 'pending'],
            ['id' => Order::STATUS_PAID, 'status' => 'paid'],
            ['id' => Order::STATUS_CANCELLED, 'status' => 'cancelled'],
            ['id' => Order::STATUS_FAILED, 'status' => 'failed'],
        ]);
    }
}
