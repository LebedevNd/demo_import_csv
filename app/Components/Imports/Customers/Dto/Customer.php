<?php

namespace App\Components\Imports\Customers\Dto;

class Customer
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $surname;

    /**
     * @var string
     */
    public $email;

    /**
     * @var string
     */
    public $age;

    /**
     * @var string
     */
    public $location;

    /**
     * @var string
     */
    public $country_code;

    /**
     * @var string|null
     */
    public $error_column;

    public function __construct(
        string $name,
        string $surname,
        string $email,
        string $age,
        string $location,
        string $country_code
    ) {
        $this->name = $name;
        $this->surname = $surname;
        $this->email = $email;
        $this->age = $age;
        $this->location = $location;
        $this->country_code = $country_code;
    }
}
