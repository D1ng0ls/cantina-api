<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use SoftDeletes;
    
    protected $dates = ['deleted_at'];

    protected $table = 'payments';

    protected $fillable = [
        'value',
        'status',
        'method',
        'order_id',
    ];

    public function order()
    {
        return $this->hasOne(Order::class);
    }
}
