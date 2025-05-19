<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pedido extends Model
{
    use SoftDeletes;
    
    protected $dates = ['deleted_at'];

    protected $table = 'pedidos';

    protected $fillable = [
        'valor_total',
        'status',
        'usuario_id',
    ];

    public function produtos()
    {
        return $this->belongsToMany(Pedido::class, 'produto_pedidos')->withPivot('quantidade', 'valor_unitario');
    }

    public function pagamentos()
    {
        return $this->belongsTo(Pagamento::class, 'pedido_id');
    }
}
