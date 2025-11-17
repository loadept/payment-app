<?php

namespace App\Products\Controllers;

use App\Products\Services\ProductService;

class ProductController
{
    public function __construct(
        private ProductService $productService
    ) {}

    public function list()
    {
        $products = $this->productService->getPaginatedProducts(10);

        return response()->json($products);
    }
}
