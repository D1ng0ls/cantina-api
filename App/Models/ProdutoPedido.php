<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProdutoPedido extends Model
{
    protected $fillable = [
        'preco_unitario',
        'quantidade',
        'produto_id',
        'pedido_id',
    ];
}
