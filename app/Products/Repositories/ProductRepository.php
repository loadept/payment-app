<?php

namespace App\Products\Repositories;

use App\Products\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductRepository
{
    public function findByIds(array $ids): Collection
    {
        return Product::whereIn('id', $ids)
            ->lockForUpdate()
            ->get();
    }

    public function findById(int $id): ?Product
    {
        return Product::find($id);
    }

    public function getPaginated(int $perPage = 10): LengthAwarePaginator
    {
        return Product::paginate($perPage, ['id', 'name', 'brand', 'price']);
    }
}
