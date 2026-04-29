<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaidaCaixa extends Model
{
    protected $table = 'saida_caixas';

    public $timestamps = false;

    protected $fillable = ['valor', 'motivo', 'data_saidacaixa'];
}