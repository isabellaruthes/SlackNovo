<?php

namespace App\Http\Requests;

use App\Support\MoneyRules;
use Illuminate\Foundation\Http\FormRequest;

class ProdutoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // A zero acquisition cost is allowed only when no consignment payment is requested.
        $pagamentoConsignado = $this->input('estado') === 'consignado'
            && $this->boolean('consignado_pago');

        return [
            'nome' => ['required', 'string', 'max:50'],
            'imagen' => ['nullable', 'image', 'max:5120'],
            'estado' => ['nullable', 'in:novo,usado,consignado'],
            'tipo_produto' => ['required', 'in:roupa,calcado'],
            'tamanho' => ['nullable', 'in:pp,p,m,g,gg,g1,g2,g3,g4,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45,46'],
            'preco_compra' => MoneyRules::rules(allowZero: ! $pagamentoConsignado),
            'preco_venda' => MoneyRules::rules(),
            'genero' => ['nullable', 'in:masculino,feminino,unissex'],
            'status' => ['sometimes', 'required', 'in:disponivel,vendido'],
            'descricao' => ['nullable', 'string', 'max:5000'],
            'cliente_consignado' => ['nullable', 'string', 'max:120'],
            'consignado_pago' => ['sometimes', 'boolean'],
            'id_categoria' => ['nullable', 'integer', 'exists:categorias,id'],
            'id_cor' => ['nullable', 'integer', 'exists:cores,id'],
            'id_material' => ['nullable', 'integer', 'exists:materiais,id'],
            'id_fornecedor' => ['nullable', 'integer', 'exists:fornecedores,id'],
        ];
    }
}
