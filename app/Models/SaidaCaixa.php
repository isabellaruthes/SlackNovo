<?php

namespace App\Models;

use App\Support\MoneyRules;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class SaidaCaixa extends Model
{
    protected $table = 'saida_caixas';

    public $timestamps = false;

    protected $fillable = ['valor', 'motivo', 'data_saidacaixa'];

    protected static function booted(): void
    {
        static::saving(function (SaidaCaixa $saida): void {
            if (! $saida->exists || $saida->isDirty('valor')) {
                Validator::make(['valor' => $saida->valor], [
                    'valor' => MoneyRules::rules(),
                ])->validate();
            }
        });
    }
}
