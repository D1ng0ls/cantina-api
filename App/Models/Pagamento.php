<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pagamento extends Model
{
    use SoftDeletes;
    
    protected $dates = ['deleted_at'];

    protected $table = 'pagamentos';

    protected $fillable = [
        'valor',
        'status',
        'metodo_pagamento',
        'pedido_id',
    ];

    public function pedido()
    {
        return $this->hasOne(Pedido::class);
    }
}
