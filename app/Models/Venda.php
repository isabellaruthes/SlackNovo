<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Venda extends Model
{
    protected $table = 'vendas';

    public $timestamps = false;

    protected $fillable = [
        'nome', 'id_produto', 'comprador', 'valor_unitario', 'valor_venda_total', 'valor_compra_total', 'data_hora', 'reembolsada',
    ];

    protected $casts = [
        'reembolsada' => 'boolean',
    ];

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class, 'id_produto');
    }
}
