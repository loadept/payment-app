<?php

namespace App\Payments\Repositories;

use App\Payments\Models\Payment;

class PaymentRepository
{
    public function create(array $payment): Payment
    {
        return Payment::create($payment);
    }

    public function insertBatch(array $payments): void
    {
        Payment::insert($payments);
    }
}
