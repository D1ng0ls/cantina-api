<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];

    protected $table = 'products';

    protected $fillable = [
        'name',
        'description',
        'image',
        'price',
        'quantity',
        'active',
        'category_id',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'categoria_id');
    }

    public function orders()
    {
        return $this->belongsToMany(Order::class, 'product_orders', 'product_id', 'order_id')
            ->withPivot('quantity', 'value_unitary')
            ->withTimestamps();
    }
}
