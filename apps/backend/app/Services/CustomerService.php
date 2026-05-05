<?php

namespace App\Services;

use App\Models\Customer;
use App\Repositories\CustomerRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CustomerService
{
    public function __construct(
        private readonly CustomerRepository $customerRepository
    ) {}

    public function createCustomer(array $data): Customer
    {
        return DB::transaction(function () use ($data) {
            /** @var Customer $customer */
            $customer = $this->customerRepository->create($data);

            return $customer;
        });
    }

    public function updateCustomer(Customer $customer, array $data): Customer
    {
        return DB::transaction(function () use ($customer, $data) {
            /** @var Customer $updated */
            $updated = $this->customerRepository->update($customer, $data);

            return $updated;
        });
    }

    public function getCustomerWithOrders(int $customerId): Customer
    {
        /** @var Customer $customer */
        $customer = $this->customerRepository->findOrFail($customerId, ['orders']);

        return $customer;
    }

    public function searchCustomers(string $query, ?int $warehouseId = null): Collection
    {
        return $this->customerRepository->search($query, $warehouseId);
    }

    public function getTopCustomers(int $limit = 10, ?int $warehouseId = null): Collection
    {
        return $this->customerRepository->getTopCustomers($limit, $warehouseId);
    }
}
