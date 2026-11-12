@php
    $produto ??= null;
    $formValue = static function (string $key, mixed $default = ''): string {
        $value = old($key, $default);

        return is_scalar($value) ? (string) $value : '';
    };
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label" for="produto_nome">Nome</label>
        <input class="form-control" id="produto_nome" name="nome" maxlength="50" value="{{ $formValue('nome', $produto->nome ?? '') }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="produto_imagen">Imagem do produto</label>
        <input class="form-control" name="imagen" type="file" accept="image/jpeg,image/png,image/gif,image/bmp,image/webp" id="produto_imagen">
        <small class="text-muted d-block mt-1">Opcional. JPEG, PNG, GIF, BMP ou WebP, até 5 MB.</small>
        @if (!empty($produto?->imagen))
            <small class="text-muted d-block mt-1">Imagem atual: {{ $produto->imagen }}</small>
        @endif
    </div>

    <div class="col-12">
        <label class="form-label d-block mb-2">Estado do produto</label>
        @php($estadoAtual = $formValue('estado', $produto->estado ?? ''))
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="estado" id="estado_novo" value="novo" @checked($estadoAtual === 'novo')>
            <label class="form-check-label" for="estado_novo">Novo</label>
        </div>
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="estado" id="estado_usado" value="usado" @checked($estadoAtual === 'usado')>
            <label class="form-check-label" for="estado_usado">Usado</label>
        </div>
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="estado" id="estado_consignado" value="consignado" @checked($estadoAtual === 'consignado')>
            <label class="form-check-label" for="estado_consignado">Consignado</label>
        </div>
    </div>
    <div class="col-12">
        <label class="form-label d-block mb-2">Tipo do produto</label>
        @php($tipoProdutoAtual = $formValue('tipo_produto', $produto->tipo_produto ?? 'roupa'))
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="tipo_produto" id="tipo_roupa" value="roupa" @checked($tipoProdutoAtual === 'roupa')>
            <label class="form-check-label" for="tipo_roupa">Roupa</label>
        </div>
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="tipo_produto" id="tipo_calcado" value="calcado" @checked($tipoProdutoAtual === 'calcado')>
            <label class="form-check-label" for="tipo_calcado">Calçado</label>
        </div>
    </div>

    <div id="consignado_fields" class="row g-3 {{ $estadoAtual === 'consignado' ? '' : 'd-none' }}">
        <div class="col-md-6">
            <label class="form-label" for="produto_cliente_consignado">Cliente que deixou a peça</label>
            <input class="form-control" id="produto_cliente_consignado" name="cliente_consignado" maxlength="120" value="{{ $formValue('cliente_consignado', $produto->cliente_consignado ?? '') }}">
        </div>
        <div class="col-md-6">
            <label class="form-label d-block">Pagamento do consignado</label>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" value="1" id="consignado_pago" name="consignado_pago" @checked($formValue('consignado_pago', $produto->consignado_pago ?? false) === '1')>
                <label class="form-check-label" for="consignado_pago">Já foi pago para a cliente</label>
            </div>
        </div>
    </div>

    <div class="col-md-3"><label class="form-label" for="preco_compra">Preço Compra</label><input class="form-control" name="preco_compra" id="preco_compra" type="number" step="0.01" min="0" max="99999999.99" value="{{ $formValue('preco_compra', $produto->preco_compra ?? '') }}" required></div>
    <div class="col-md-3"><label class="form-label" for="produto_preco_venda">Preço Venda</label><input class="form-control" id="produto_preco_venda" name="preco_venda" type="number" step="0.01" min="0.01" max="99999999.99" value="{{ $formValue('preco_venda', $produto->preco_venda ?? '') }}" required></div>
    <div class="col-md-3">
        @php($tamanhoAtual = $formValue('tamanho', $produto->tamanho ?? ''))
        {{-- Only the select of the current type is enabled, so the form works without JavaScript. --}}
        @php($isCalcadoAtual = $tipoProdutoAtual === 'calcado')
        <label class="form-label" for="{{ $isCalcadoAtual ? 'tamanho_calcado' : 'tamanho_roupa' }}" id="tamanho_label">Tamanho</label>
        <select class="form-select {{ $isCalcadoAtual ? 'd-none' : '' }}" name="tamanho" id="tamanho_roupa" @disabled($isCalcadoAtual)>
            <option value="">-</option>
            @foreach (\App\Http\Requests\ProdutoRequest::TAMANHOS_ROUPA as $tamanho)
                <option value="{{ $tamanho }}" @selected($tamanhoAtual === $tamanho)>{{ strtoupper($tamanho) }}</option>
            @endforeach
        </select>
        <select class="form-select {{ $isCalcadoAtual ? '' : 'd-none' }}" name="tamanho" id="tamanho_calcado" @disabled(! $isCalcadoAtual)>
            <option value="">-</option>
            @foreach (\App\Http\Requests\ProdutoRequest::TAMANHOS_CALCADO as $numero)
                <option value="{{ $numero }}" @selected($tamanhoAtual === $numero)>{{ $numero }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3"><label class="form-label" for="produto_status">Status</label><select class="form-select" name="status" id="produto_status"><option value="disponivel">Disponível</option><option value="vendido" @selected($formValue('status', $produto->status ?? '')==='vendido')>Vendido</option></select></div>
    <div class="col-md-3"><label class="form-label" for="produto_genero">Gênero</label><select class="form-select" name="genero" id="produto_genero"><option value="">-</option><option value="masculino" @selected($formValue('genero', $produto->genero ?? '') === 'masculino')>Masculino</option><option value="feminino" @selected($formValue('genero', $produto->genero ?? '') === 'feminino')>Feminino</option><option value="unissex" @selected($formValue('genero', $produto->genero ?? '') === 'unissex')>Unissex</option></select></div>
    <div class="col-md-3"><label class="form-label" for="produto_id_categoria">Categoria</label><select class="form-select" name="id_categoria" id="produto_id_categoria"><option value="">-</option>@foreach($categorias as $item)<option value="{{ $item->id }}" @selected($formValue('id_categoria', $produto->id_categoria ?? '')===(string)$item->id)>{{ $item->nome }}</option>@endforeach</select></div>
    <div class="col-md-3"><label class="form-label" for="produto_id_cor">Cor</label><select class="form-select" name="id_cor" id="produto_id_cor"><option value="">-</option>@foreach($cores as $item)<option value="{{ $item->id }}" @selected($formValue('id_cor', $produto->id_cor ?? '')===(string)$item->id)>{{ $item->nome }}</option>@endforeach</select></div>
    <div class="col-md-3"><label class="form-label" for="produto_id_material">Material</label><select class="form-select" name="id_material" id="produto_id_material"><option value="">-</option>@foreach($materiais as $item)<option value="{{ $item->id }}" @selected($formValue('id_material', $produto->id_material ?? '')===(string)$item->id)>{{ $item->nome }}</option>@endforeach</select></div>
    <div class="col-md-3"><label class="form-label" for="produto_id_fornecedor">Fornecedor</label><select class="form-select" name="id_fornecedor" id="produto_id_fornecedor"><option value="">-</option>@foreach($fornecedores as $item)<option value="{{ $item->id }}" @selected($formValue('id_fornecedor', $produto->id_fornecedor ?? '')===(string)$item->id)>{{ $item->nome }}</option>@endforeach</select></div>
    <div class="col-12"><label class="form-label" for="produto_descricao">Descrição</label><textarea class="form-control" name="descricao" rows="3" maxlength="5000" id="produto_descricao">{{ $formValue('descricao', $produto->descricao ?? '') }}</textarea></div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const consignadoFields = document.getElementById('consignado_fields');
        const consignadoPago = document.getElementById('consignado_pago');
        const precoCompra = document.getElementById('preco_compra');
        const radios = document.querySelectorAll('input[name="estado"]');
        const tipoProdutoRadios = document.querySelectorAll('input[name="tipo_produto"]');
        const tamanhoRoupa = document.getElementById('tamanho_roupa');
        const tamanhoCalcado = document.getElementById('tamanho_calcado');
        const tamanhoLabel = document.getElementById('tamanho_label');

        function toggleConsignadoFields() {
            const selecionado = document.querySelector('input[name="estado"]:checked')?.value;
            consignadoFields.classList.toggle('d-none', selecionado !== 'consignado');
            precoCompra.min = selecionado === 'consignado' && consignadoPago.checked ? '0.01' : '0';
        }

        radios.forEach(radio => radio.addEventListener('change', toggleConsignadoFields));
        consignadoPago.addEventListener('change', toggleConsignadoFields);
        tipoProdutoRadios.forEach(radio => radio.addEventListener('change', function () {
            const tipoSelecionado = document.querySelector('input[name=\"tipo_produto\"]:checked')?.value;
            const isCalcado = tipoSelecionado === 'calcado';

            tamanhoRoupa.classList.toggle('d-none', isCalcado);
            tamanhoCalcado.classList.toggle('d-none', !isCalcado);

            tamanhoLabel.htmlFor = isCalcado ? 'tamanho_calcado' : 'tamanho_roupa';
            tamanhoRoupa.disabled = isCalcado;
            tamanhoCalcado.disabled = !isCalcado;
        }));

        const tipoSelecionadoInicial = document.querySelector('input[name=\"tipo_produto\"]:checked')?.value;
        const isCalcadoInicial = tipoSelecionadoInicial === 'calcado';
        tamanhoRoupa.classList.toggle('d-none', isCalcadoInicial);
        tamanhoCalcado.classList.toggle('d-none', !isCalcadoInicial);
        tamanhoLabel.htmlFor = isCalcadoInicial ? 'tamanho_calcado' : 'tamanho_roupa';
        tamanhoRoupa.disabled = isCalcadoInicial;
        tamanhoCalcado.disabled = !isCalcadoInicial;
        toggleConsignadoFields();
    });
</script>
