<?php

namespace App\Products\Services;

use App\Products\Repositories\ProductOrderRepository;
use App\Products\Repositories\ProductRepository;
use App\Payments\Repositories\PaymentPlanRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductService
{
    public function __construct(
        private ProductRepository $productRepository,
        private ProductOrderRepository $productOrderRepository,
        private PaymentPlanRepository $paymentPlanRepository
    ) {}

    public function getPaginatedProducts(int $perPage = 10): LengthAwarePaginator
    {
        return $this->productRepository->getPaginated($perPage);
    }
}