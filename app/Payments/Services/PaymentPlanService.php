<?php

namespace App\Payments\Services;

use App\Payments\Repositories\PaymentPlanRepository;

class PaymentPlanService
{
    public function __construct(
        private PaymentPlanRepository $paymentPlanRepository
    ) {}

    public function getPaymentPlans(int $orderId): array
    {
        return $this->paymentPlanRepository->getPendingPayments($orderId);
    }
}
