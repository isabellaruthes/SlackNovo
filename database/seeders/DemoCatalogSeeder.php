<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Cor;
use App\Models\Fornecedor;
use App\Models\Material;
use App\Models\Produto;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DemoCatalogSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            throw new RuntimeException('Dados demonstrativos disponíveis apenas em ambiente local ou de testes.');
        }

        DB::transaction(function (): void {
            $categories = collect(['Camisetas', 'Camisas', 'Calças', 'Vestidos', 'Casacos', 'Calçados'])
                ->mapWithKeys(fn ($name) => [$name => Categoria::firstOrCreate(['nome' => $name])->id]);
            $colors = collect(['Preto', 'Branco', 'Azul', 'Verde', 'Vermelho', 'Bege', 'Rosa', 'Cinza'])
                ->map(fn ($name) => Cor::firstOrCreate(['nome' => $name])->id)->all();
            $materials = collect(['Algodão', 'Jeans', 'Linho', 'Poliéster', 'Lã', 'Couro sintético'])
                ->mapWithKeys(fn ($name) => [$name => Material::firstOrCreate(['nome' => $name])->id]);
            $suppliers = collect(['Ateliê Aurora (Demo)', 'Brechó Circular (Demo)', 'Malharia Horizonte (Demo)', 'Calçados Passo Leve (Demo)'])
                ->map(fn ($name) => Fornecedor::firstOrCreate(['nome' => $name])->id)->all();

            $models = [
                ['Camiseta básica', 'Camisetas', 'Algodão', 'roupa', 'unissex', 15, 39.90],
                ['Camiseta estampada', 'Camisetas', 'Algodão', 'roupa', 'unissex', 20, 49.90],
                ['Camisa social', 'Camisas', 'Linho', 'roupa', 'masculino', 35, 89.90],
                ['Calça jeans reta', 'Calças', 'Jeans', 'roupa', 'feminino', 40, 99.90],
                ['Calça de linho', 'Calças', 'Linho', 'roupa', 'unissex', 45, 119.90],
                ['Vestido floral', 'Vestidos', 'Poliéster', 'roupa', 'feminino', 30, 79.90],
                ['Vestido longo', 'Vestidos', 'Algodão', 'roupa', 'feminino', 55, 139.90],
                ['Casaco de inverno', 'Casacos', 'Lã', 'roupa', 'unissex', 70, 179.90],
                ['Tênis casual', 'Calçados', 'Couro sintético', 'calcado', 'unissex', 60, 149.90],
                ['Sandália confortável', 'Calçados', 'Couro sintético', 'calcado', 'feminino', 25, 69.90],
            ];

            foreach ($models as $index => [$name, $category, $material, $type, $gender, $cost, $price]) {
                for ($variant = 0; $variant < 3; $variant++) {
                    $state = ['novo', 'usado', 'consignado'][$variant];
                    Produto::firstOrCreate(['nome' => $name.' Demo '.($variant + 1)], [
                        'tipo_produto' => $type,
                        'estado' => $state,
                        'tamanho' => $type === 'calcado' ? (string) (36 + $variant * 2) : ['p', 'm', 'g'][$variant],
                        'preco_compra' => $cost,
                        'preco_venda' => $price + $variant * 5,
                        'genero' => $gender,
                        'status' => 'disponivel',
                        'imagen' => null,
                        'descricao' => 'Produto fictício para testes de cadastro, filtros e vendas. Estado: '.$state.'.',
                        'cliente_consignado' => $state === 'consignado' ? 'Cliente demonstração '.($index + 1) : null,
                        'consignado_pago' => false,
                        'id_categoria' => $categories[$category],
                        'id_cor' => $colors[($index + $variant) % count($colors)],
                        'id_material' => $materials[$material],
                        'id_fornecedor' => $suppliers[$index % count($suppliers)],
                    ]);
                }
            }
        });
    }
}
