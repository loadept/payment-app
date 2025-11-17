<?php

namespace App\Orders\Controllers;

use App\Http\Requests\ValidateUserRequest;
use App\Orders\Services\OrderService;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class OrderController
{
    public function __construct(
        private OrderService $orderService
    ) {}

    public function getOrdersByUser(ValidateUserRequest $request, ?int $orderId = null)
    {
        try {
            $userId = $request->validated()['x-user-id'];
            $result = $this->orderService->getOrdersByUser($userId, $orderId);

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Error fetching orders: ' . $e->getMessage());

            return response()->json([
                'error' => 'Could not fetch orders',
            ], 500);
        }
    }

    public function create(ValidateUserRequest $request)
    {
        try {
            $validated = $request->validate([
                'products'            => 'required|array',
                'products.*.id'       => 'required|integer|exists:product,id',
                'products.*.quantity' => 'required|integer|min:1',
                'installments'        => 'required|integer|min:1',
            ]);

            $userId = $request->validated()['x-user-id'];

            $result = $this->orderService->createOrder(
                $userId,
                $validated['products'],
                $validated['installments']
            );

            return response()->json($result, 201);
        } catch (ValidationException $e) {
            Log::warning('Order validation failed: ' . json_encode($e->errors()));

            return response()->json([
                'message' => $e->getMessage(),
                'errors'  => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creating order: ' . $e->getMessage());

            return response()->json([
                'message' => 'Order creation failed',
            ], 500);
        }
    }
}
