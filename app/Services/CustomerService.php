<?php

namespace App\Services;

use App\Models\Customer;
use \App\Components\Imports\Customers\Dto\Customer as CustomerDto;

class CustomerService
{
    /**
     * @var Customer
     */
    private $model;

    public function __construct(Customer $model){
        $this->model = $model;
    }

    public function storeCustomer(CustomerDto $customer_dto): Customer
    {
        /** @var Customer $customer */
        $customer = $this->model->newQuery()->updateOrCreate(
            [
                'email' => $customer_dto->email
            ],
            [
                'name' => $customer_dto->name,
                'surname' => $customer_dto->surname,
                'age' => $customer_dto->age,
                'location' => $customer_dto->location,
                'country_code' => $customer_dto->country_code
            ]
        );

        return $customer;
    }
}
