<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Produto extends Model
{
    use SoftDeletes;
    
    protected $dates = ['deleted_at'];

    protected $table = 'produtos';

    protected $fillable = [
        'nome',
        'descricao',
        'imagem',
        'preco',
        'quantidade',
        'ativo',
        'categoria_id',
    ];

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function pedidos()
    {
        return $this->belongsToMany(Produto::class, 'produto_pedidos')->withPivot('quantidade', 'valor_unitario'); 
    }
}
