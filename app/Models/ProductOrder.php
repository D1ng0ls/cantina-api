<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductOrder extends Model
{
    protected $fillable = [
        'value_unitary',
        'quantity',
        'product_id',
        'order_id',
    ];
}
