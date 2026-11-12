<?php

// Mensagens de validação em português (SLK-09). Regras não listadas usam o idioma de
// fallback (en). Os nomes de campo ficam em "attributes" para nunca exibir o nome técnico.

return [

    'accepted' => 'O campo :attribute deve ser aceito.',
    'after' => 'O campo :attribute deve ser uma data posterior a :date.',
    'after_or_equal' => 'O campo :attribute deve ser uma data igual ou posterior a :date.',
    'alpha' => 'O campo :attribute deve conter apenas letras.',
    'alpha_num' => 'O campo :attribute deve conter apenas letras e números.',
    'array' => 'O campo :attribute deve ser uma lista.',
    'before' => 'O campo :attribute deve ser uma data anterior a :date.',
    'before_or_equal' => 'O campo :attribute deve ser uma data igual ou anterior a :date.',
    'between' => [
        'array' => 'O campo :attribute deve ter entre :min e :max itens.',
        'file' => 'O arquivo :attribute deve ter entre :min e :max kilobytes.',
        'numeric' => 'O campo :attribute deve estar entre :min e :max.',
        'string' => 'O campo :attribute deve ter entre :min e :max caracteres.',
    ],
    'boolean' => 'O campo :attribute deve ser verdadeiro ou falso.',
    'confirmed' => 'A confirmação do campo :attribute não confere.',
    'date' => 'O campo :attribute deve ser uma data válida.',
    'date_format' => 'O campo :attribute deve estar no formato :format.',
    'decimal' => 'O campo :attribute deve ter :decimal casas decimais.',
    'different' => 'Os campos :attribute e :other devem ser diferentes.',
    'digits' => 'O campo :attribute deve ter :digits dígitos.',
    'email' => 'O campo :attribute deve ser um e-mail válido.',
    'exists' => 'O valor selecionado em :attribute não existe.',
    'file' => 'O campo :attribute deve ser um arquivo.',
    'filled' => 'O campo :attribute deve ser preenchido.',
    'gt' => [
        'numeric' => 'O campo :attribute deve ser maior que :value.',
        'string' => 'O campo :attribute deve ter mais de :value caracteres.',
    ],
    'gte' => [
        'numeric' => 'O campo :attribute deve ser maior ou igual a :value.',
        'string' => 'O campo :attribute deve ter :value caracteres ou mais.',
    ],
    'image' => 'O arquivo :attribute deve ser uma imagem (JPEG, PNG, GIF, BMP ou WebP).',
    'in' => 'O valor selecionado em :attribute é inválido.',
    'integer' => 'O campo :attribute deve ser um número inteiro.',
    'lt' => [
        'numeric' => 'O campo :attribute deve ser menor que :value.',
        'string' => 'O campo :attribute deve ter menos de :value caracteres.',
    ],
    'lte' => [
        'numeric' => 'O campo :attribute deve ser menor ou igual a :value.',
        'string' => 'O campo :attribute deve ter :value caracteres ou menos.',
    ],
    'max' => [
        'array' => 'O campo :attribute deve ter no máximo :max itens.',
        'file' => 'O arquivo :attribute deve ter no máximo :max kilobytes.',
        'numeric' => 'O campo :attribute deve ser no máximo :max.',
        'string' => 'O campo :attribute deve ter no máximo :max caracteres.',
    ],
    'mimes' => 'O arquivo :attribute deve ser do tipo: :values.',
    'mimetypes' => 'O arquivo :attribute deve ser do tipo: :values.',
    'min' => [
        'array' => 'O campo :attribute deve ter pelo menos :min itens.',
        'file' => 'O arquivo :attribute deve ter pelo menos :min kilobytes.',
        'numeric' => 'O campo :attribute deve ser no mínimo :min.',
        'string' => 'O campo :attribute deve ter pelo menos :min caracteres.',
    ],
    'not_in' => 'O valor selecionado em :attribute é inválido.',
    'numeric' => 'O campo :attribute deve ser um número.',
    'present' => 'O campo :attribute deve estar presente.',
    'prohibited' => 'O campo :attribute não é permitido.',
    'regex' => 'O formato do campo :attribute é inválido.',
    'required' => 'O campo :attribute é obrigatório.',
    'required_if' => 'O campo :attribute é obrigatório quando :other é :value.',
    'required_with' => 'O campo :attribute é obrigatório quando :values está presente.',
    'same' => 'Os campos :attribute e :other devem ser iguais.',
    'size' => [
        'array' => 'O campo :attribute deve ter :size itens.',
        'file' => 'O arquivo :attribute deve ter :size kilobytes.',
        'numeric' => 'O campo :attribute deve ser :size.',
        'string' => 'O campo :attribute deve ter :size caracteres.',
    ],
    'string' => 'O campo :attribute deve ser um texto.',
    'unique' => 'Já existe um registro com este :attribute.',
    'uploaded' => 'Não foi possível enviar o arquivo :attribute. Verifique se ele tem até 5 MB e tente novamente.',

    /*
    | Mensagens específicas: valores em dinheiro usam ponto como separador decimal
    | (é o que o campo numérico do navegador envia) e no máximo 2 casas.
    */
    'custom' => [
        'valor' => ['regex' => 'Informe o :attribute como um número positivo com até 2 casas decimais (ex.: 10.50).'],
        'valor_venda' => ['regex' => 'Informe o :attribute como um número positivo com até 2 casas decimais (ex.: 10.50).'],
        'preco_compra' => ['regex' => 'Informe o :attribute como um número positivo com até 2 casas decimais (ex.: 10.50).'],
        'preco_venda' => ['regex' => 'Informe o :attribute como um número positivo com até 2 casas decimais (ex.: 10.50).'],
        'fim' => ['after_or_equal' => 'A data final deve ser igual ou posterior à data inicial.'],
    ],

    'attributes' => [
        'nome' => 'nome',
        'password' => 'senha',
        'valor' => 'valor da saída',
        'motivo' => 'motivo',
        'valor_venda' => 'valor da venda',
        'comprador' => 'comprador',
        'id_produto' => 'ID do produto',
        'imagen' => 'imagem do produto',
        'estado' => 'estado do produto',
        'tipo_produto' => 'tipo do produto',
        'tamanho' => 'tamanho',
        'preco_compra' => 'preço de compra',
        'preco_venda' => 'preço de venda',
        'genero' => 'gênero',
        'status' => 'status',
        'descricao' => 'descrição',
        'cliente_consignado' => 'cliente consignante',
        'consignado_pago' => 'pagamento à consignante',
        'id_categoria' => 'categoria',
        'id_cor' => 'cor',
        'id_material' => 'material',
        'id_fornecedor' => 'fornecedor',
        'categoria' => 'categoria',
        'q' => 'pesquisa',
        'ordenar' => 'ordenação',
        'inicio' => 'data inicial',
        'fim' => 'data final',
    ],

];
