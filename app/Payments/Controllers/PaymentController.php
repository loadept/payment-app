<?php

namespace App\Payments\Controllers;

use App\Exceptions\ExternalApiException;
use App\Exceptions\InvalidPaymentException;
use App\Http\Requests\ValidateUserRequest;
use App\Orders\Services\OrderService;
use App\Payments\Services\PaymentService;
use App\Payments\Services\PaymentPlanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PaymentController
{
    public function __construct(
        private PaymentPlanService $paymentPlanService,
        private PaymentService $paymentService,
        private OrderService $orderService,
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function getPaymentPlans(int $orderId)
    {
        try {
            $data = $this->paymentPlanService->getPaymentPlans($orderId);
            if (empty($data)) {
                return response()->json(['message' => 'No payment plans found'], 404);
            }

            return response()->json($data);
        } catch (\Exception $e) {
            Log::error('Error fetching payment plans for order ' . $orderId . ': ' . $e->getMessage());

            return response()->json(['error' => 'Unable to fetch payment plans'], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function payOrderment(ValidateUserRequest $request)
    {
        try {
            $validated = $request->validate([
                'order_id'          => 'required|integer|exists:order,id',
                'total_payment'     => 'required|numeric|min:0.01',
                'installment_number'=> 'required|integer|min:1',
            ]);
            $userId = $request->validated()['x-user-id'];

            $order = $this->orderService->getOrderByUser($userId, $validated['order_id']);
            if (!$order) {
                return response()->json(['error' => 'No existing payment plan for this customer'], 404);
            }

            $result = $this->paymentService->createPayment(
                $validated['order_id'],
                $validated['total_payment'],
                $validated['installment_number']
            );

            return response()->json($result, 201);
        } catch (ValidationException $e) {
            Log::warning('Payment validation failed: ' . json_encode($e->errors()));

            return response()->json([
                'message' => $e->getMessage(),
                'errors'  => $e->errors()
            ], 422);
        } catch (InvalidPaymentException $e) {
            Log::warning('Invalid payment attempt: ' . $e->getMessage());

            return response()->json(['error' => $e->getMessage()], 400);
        } catch (ExternalApiException $e) {
            Log::error('External payment API error: ' . $e->getMessage());

            return response()->json([
                'message' => 'Payment processing failed due to external service',
                'error' => $e->getMessage()
            ], 502);

        } catch (\Exception $e) {
            Log::error('Error processing payment: ' . $e->getMessage());

            return response()->json(['error' => 'Payment processing failed'], 500);
        }
    }
}
