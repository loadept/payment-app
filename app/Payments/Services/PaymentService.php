<?php

namespace App\Payments\Services;

use App\Exceptions\ExternalApiException;
use App\Exceptions\InvalidPaymentException;
use App\Orders\Models\Order;
use App\Orders\Repositories\OrderRepository;
use App\Payments\Models\Payment;
use App\Payments\Repositories\PaymentPlanRepository;
use App\Payments\Repositories\PaymentRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class PaymentService
{
    public function __construct(
        private OrderRepository $orderRepository,
        private PaymentRepository $paymentRepository,
        private PaymentPlanRepository $paymentPlanRepository
    ) {}

    public function createPayment(int $orderId, float $amount, int $installmentNumber): array
    {
        $committed = false;
        
        try {
            DB::beginTransaction();

            $installmentStatus = $this->paymentPlanRepository->getInstallmentStatus($orderId, $installmentNumber);
            if (!$installmentStatus) {
                throw new InvalidPaymentException(
                    "Installment number {$installmentNumber} does not exist for order {$orderId}"
                );
            }
            if ($installmentStatus->is_paid) {
                throw new InvalidPaymentException(
                    "Installment number {$installmentNumber} for order {$orderId} is already paid"
                );
            }

            $plan = $this->paymentPlanRepository->getLastPendingPayment($orderId);
            if (!$plan) {
                throw new InvalidPaymentException('No pending payment plan found for order ' . $orderId);
            }

            if ($plan->installment_number !== $installmentNumber) {
                $payment = $this->paymentRepository->create([
                    'payment_plan_id' => $plan->id,
                    'amount'          => $amount,
                    'is_success'      => false,
                ]);

                $this->orderRepository->updateStatus($orderId, [
                    'order_status_id' => Order::STATUS_FAILED,
                ]);

                DB::commit();
                $committed = true;

                throw new InvalidPaymentException(
                    "Must pay installment {$plan->installment_number} first, but received {$installmentNumber}"
                );
            }

            if (floatval($amount) !== floatval($plan->amount)) {
                $payment = $this->paymentRepository->create([
                    'payment_plan_id' => $plan->id,
                    'amount'          => $amount,
                    'is_success'      => false,
                ]);

                $this->orderRepository->updateStatus($orderId, [
                    'order_status_id' => Order::STATUS_FAILED,
                ]);

                DB::commit();
                $committed = true;

                throw new InvalidPaymentException(
                    "Incorrect amount. Expected: {$plan->amount}, received: {$amount}"
                );
            }

            $externalApiResponse = $this->requestPaymentApi($amount, $plan->order->customer_id, $orderId);

            if (!$externalApiResponse['success']) {
                $payment = $this->paymentRepository->create([
                    'payment_plan_id' => $plan->id,
                    'amount'          => $amount,
                    'is_success'      => false,
                ]);

                $this->orderRepository->updateStatus($orderId, [
                    'order_status_id' => Order::STATUS_FAILED,
                ]);

                DB::commit();
                $committed = true;

                throw new ExternalApiException($externalApiResponse['message']);
            }

            $this->paymentPlanRepository->updatePendingPayment($plan->id, [
                'is_paid' => true,
            ]);

            $payment = $this->paymentRepository->create([
                'payment_plan_id' => $plan->id,
                'amount'          => $amount,
                'transaction_id'  => $externalApiResponse['transaction_id'],
                'is_success'      => true,
            ]);

            $allPaid = $this->paymentPlanRepository->areAllPaid($orderId);
            
            $this->orderRepository->updateStatus($orderId, [
                'order_status_id' => $allPaid ? Order::STATUS_PAID : Order::STATUS_PENDING,
            ]);

            DB::commit();
            $committed = true;

            return [
                'payment_id' => $payment->id,
                'order_status' => $allPaid ? 'paid' : 'pending',
                'message' => $allPaid 
                    ? 'Payment successful. All installments paid.' 
                    : 'Payment successful. Pending installments remaining.',
                'transaction_id' => $externalApiResponse['transaction_id']
            ];
        } catch (\Exception $e) {
            if (!$committed) {
                DB::rollBack();
            }
            throw $e;
        }
    }

    public function requestPaymentApi(float $amount, int $userId, int $orderId): array
    {
        $response = Http::post(env('EXTERNAL_PAYMENT_API_URL') . "/pay", [
            'amount'  => $amount,
            'user_id' => $userId,
            'order_id' => $orderId,
        ]);

        $statusCode = $response->status();
        if ($statusCode !== 200) {
            return $response->json();
        }

        return $response->json();
    }
}
