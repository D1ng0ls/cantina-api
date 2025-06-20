<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];

    protected $table = 'orders';

    protected $fillable = [
        'total_value',
        'payment_url',
        'status',
        'user_id',
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_orders', 'order_id', 'product_id')
            ->withPivot('quantity', 'value_unitary')
            ->withTimestamps();
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class, 'order_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
