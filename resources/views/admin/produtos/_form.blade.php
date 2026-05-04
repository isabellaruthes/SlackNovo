<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Nome</label>
        <input class="form-control" name="nome" value="{{ old('nome', $produto->nome ?? '') }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Imagem do produto</label>
        <input class="form-control" name="imagen" type="file" accept="image/*">
        @if (!empty($produto?->imagen))
            <small class="text-muted d-block mt-1">Imagem atual: {{ $produto->imagen }}</small>
        @endif
    </div>

    <div class="col-12">
        <label class="form-label d-block mb-2">Estado do produto</label>
        @php($estadoAtual = old('estado', $produto->estado ?? ''))
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
        @php($tipoProdutoAtual = old('tipo_produto', $produto->tipo_produto ?? 'roupa'))
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
            <label class="form-label">Cliente que deixou a peça</label>
            <input class="form-control" name="cliente_consignado" value="{{ old('cliente_consignado', $produto->cliente_consignado ?? '') }}">
        </div>
        <div class="col-md-6">
            <label class="form-label d-block">Pagamento do consignado</label>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" value="1" id="consignado_pago" name="consignado_pago" @checked(old('consignado_pago', $produto->consignado_pago ?? false))>
                <label class="form-check-label" for="consignado_pago">Já foi pago para a cliente</label>
            </div>
        </div>
    </div>

    <div class="col-md-3"><label class="form-label">Preço Compra</label><input class="form-control" name="preco_compra" type="number" step="0.01" value="{{ old('preco_compra', $produto->preco_compra ?? '') }}" required></div>
    <div class="col-md-3"><label class="form-label">Preço Venda</label><input class="form-control" name="preco_venda" type="number" step="0.01" value="{{ old('preco_venda', $produto->preco_venda ?? '') }}" required></div>
    <div class="col-md-3">
        <label class="form-label">Tamanho</label>
        @php($tamanhoAtual = old('tamanho', $produto->tamanho ?? ''))
        <select class="form-select" name="tamanho" id="tamanho_roupa">
            <option value="">-</option>
            @foreach (['pp', 'p', 'm', 'g', 'gg', 'g1', 'g2', 'g3', 'g4'] as $tamanho)
                <option value="{{ $tamanho }}" @selected($tamanhoAtual === $tamanho)>{{ strtoupper($tamanho) }}</option>
            @endforeach
        </select>
        <select class="form-select d-none" name="tamanho" id="tamanho_calcado">
            <option value="">-</option>
            @foreach (range(14, 46) as $numero)
                <option value="{{ $numero }}" @selected($tamanhoAtual === (string) $numero)>{{ $numero }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3"><label class="form-label">Status</label><select class="form-select" name="status"><option value="disponivel">Disponível</option><option value="vendido" @selected(old('status', $produto->status ?? '')==='vendido')>Vendido</option></select></div>
    <div class="col-md-3"><label class="form-label">Gênero</label><select class="form-select" name="genero"><option value="">-</option><option value="masculino">Masculino</option><option value="feminino">Feminino</option><option value="unissex">Unissex</option></select></div>
    <div class="col-md-3"><label class="form-label">Categoria</label><select class="form-select" name="id_categoria"><option value="">-</option>@foreach($categorias as $item)<option value="{{ $item->id }}" @selected((string)old('id_categoria', $produto->id_categoria ?? '')===(string)$item->id)>{{ $item->nome }}</option>@endforeach</select></div>
    <div class="col-md-3"><label class="form-label">Cor</label><select class="form-select" name="id_cor"><option value="">-</option>@foreach($cores as $item)<option value="{{ $item->id }}" @selected((string)old('id_cor', $produto->id_cor ?? '')===(string)$item->id)>{{ $item->nome }}</option>@endforeach</select></div>
    <div class="col-md-3"><label class="form-label">Material</label><select class="form-select" name="id_material"><option value="">-</option>@foreach($materiais as $item)<option value="{{ $item->id }}" @selected((string)old('id_material', $produto->id_material ?? '')===(string)$item->id)>{{ $item->nome }}</option>@endforeach</select></div>
    <div class="col-md-3"><label class="form-label">Fornecedor</label><select class="form-select" name="id_fornecedor"><option value="">-</option>@foreach($fornecedores as $item)<option value="{{ $item->id }}" @selected((string)old('id_fornecedor', $produto->id_fornecedor ?? '')===(string)$item->id)>{{ $item->nome }}</option>@endforeach</select></div>
    <div class="col-12"><label class="form-label">Descrição</label><textarea class="form-control" name="descricao" rows="3">{{ old('descricao', $produto->descricao ?? '') }}</textarea></div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const consignadoFields = document.getElementById('consignado_fields');
        const radios = document.querySelectorAll('input[name="estado"]');
        const tipoProdutoRadios = document.querySelectorAll('input[name="tipo_produto"]');
        const tamanhoRoupa = document.getElementById('tamanho_roupa');
        const tamanhoCalcado = document.getElementById('tamanho_calcado');

        function toggleConsignadoFields() {
            const selecionado = document.querySelector('input[name="estado"]:checked')?.value;
            consignadoFields.classList.toggle('d-none', selecionado !== 'consignado');
        }

        radios.forEach(radio => radio.addEventListener('change', toggleConsignadoFields));
        tipoProdutoRadios.forEach(radio => radio.addEventListener('change', function () {
            const tipoSelecionado = document.querySelector('input[name=\"tipo_produto\"]:checked')?.value;
            const isCalcado = tipoSelecionado === 'calcado';

            tamanhoRoupa.classList.toggle('d-none', isCalcado);
            tamanhoCalcado.classList.toggle('d-none', !isCalcado);

            tamanhoRoupa.disabled = isCalcado;
            tamanhoCalcado.disabled = !isCalcado;
        }));

        const tipoSelecionadoInicial = document.querySelector('input[name=\"tipo_produto\"]:checked')?.value;
        const isCalcadoInicial = tipoSelecionadoInicial === 'calcado';
        tamanhoRoupa.classList.toggle('d-none', isCalcadoInicial);
        tamanhoCalcado.classList.toggle('d-none', !isCalcadoInicial);
        tamanhoRoupa.disabled = isCalcadoInicial;
        tamanhoCalcado.disabled = !isCalcadoInicial;
        toggleConsignadoFields();
    });
</script>
