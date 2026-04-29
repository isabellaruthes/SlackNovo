<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Material extends Model
{
    protected $table = 'materiais';

    protected $fillable = ['nome'];

    public function produtos(): HasMany
    {
        return $this->hasMany(Produto::class, 'id_material');
    }
}