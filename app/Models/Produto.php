<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Produto extends Model
{
    protected $table = 'produtos';

    protected $fillable = [
        'imagen', 'nome', 'estado', 'cliente_consignado', 'consignado_pago', 'tamanho', 'preco_compra', 'preco_venda', 'genero', 'status', 'descricao',
        'id_categoria', 'id_cor', 'id_material', 'id_fornecedor',
    ];

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'id_categoria');
    }

    public function cor(): BelongsTo
    {
        return $this->belongsTo(Cor::class, 'id_cor');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'id_material');
    }

    public function fornecedor(): BelongsTo
    {
        return $this->belongsTo(Fornecedor::class, 'id_fornecedor');
    }
}