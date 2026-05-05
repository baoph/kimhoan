<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class ProductRepository extends BaseRepository
{
    protected function getModel(): Model
    {
        return new Product();
    }

    public function query(): Builder
    {
        return $this->model->newQuery();
    }

    public function search(string $query): Collection
    {
        return $this->query()->search($query)->get();
    }

    public function getByCategory(int $categoryId): Collection
    {
        return $this->query()->byCategory($categoryId)->get();
    }

    public function getLowStock(int $threshold = 10): Collection
    {
        return $this->query()
            ->active()
            ->lowStock($threshold)
            ->with('warehouseStocks.warehouse')
            ->get();
    }

    public function getByIdsForUpdate(array $productIds): Collection
    {
        return $this->query()
            ->whereIn('id', $productIds)
            ->lockForUpdate()
            ->get();
    }
}
