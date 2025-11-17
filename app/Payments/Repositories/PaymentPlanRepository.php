<?php

namespace App\Payments\Repositories;

use App\Payments\Models\PaymentPlan;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PaymentPlanRepository
{
    public function getLastPendingPayment(int $orderId): PaymentPlan|null
    {
        return PaymentPlan::where('order_id', $orderId)
            ->where('is_paid', false)
            ->orderBy('installment_number', 'asc')
            ->first();
    }

    public function getInstallmentStatus(int $orderId, int $installmentNumber): PaymentPlan|null
    {
        return PaymentPlan::where('order_id', $orderId)
            ->where('installment_number', $installmentNumber)
            ->first(['is_paid']);
    }

    public function getPendingPayments(int $orderId): array
    {
        $result = DB::selectOne("
            SELECT
                json_build_object(
                    'customer', u.name,
                    'order', pp.order_id,
                    'total_installments', count(pp.id),
                    'installments', json_agg(
                        json_build_object(
                            'installment', installment_number,
                            'amount', amount,
                            'is_paid', is_paid
                        )
                    )
                ) AS data
            FROM payment_plan pp
            JOIN \"order\" o ON o.id = pp.order_id
            JOIN users u ON u.id = o.customer_id
            WHERE order_id = ?
            AND is_paid = FALSE
            GROUP BY u.name, pp.order_id
        ", [$orderId]);

        if (!$result) {
            return [];
        }

        return json_decode($result->data, true);
    }

    public function updatePendingPayment(int $paymentPlanId, array $data): void
    {
        PaymentPlan::where('id', $paymentPlanId)->update($data);
    }

    public function areAllPaid(int $orderId): bool
    {
        $total = PaymentPlan::where('order_id', $orderId)->count();
        $paid = PaymentPlan::where('order_id', $orderId)
            ->where('is_paid', true)
            ->count();
        
        return $total === $paid && $total > 0;
    }

    public function insertBatch(array $paymentPlans): void
    {
        PaymentPlan::insert($paymentPlans);
    }

}
