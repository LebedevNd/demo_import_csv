<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $name
 * @property string $surname
 * @property string $email
 * @property string $age
 * @property string $location
 * @property string $country_code
 */
class Customer extends Model
{
    use HasFactory;

    protected $table = 'customers';

    protected $guarded = [];
}
