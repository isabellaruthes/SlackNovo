<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Nome</label>
        <input class="form-control" name="nome" value="{{ old('nome', $produto->nome ?? '') }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Imagem (nome do arquivo)</label>
        <input class="form-control" name="imagen" value="{{ old('imagen', $produto->imagen ?? '') }}">
    </div>
    <div class="col-md-3"><label class="form-label">Preço Compra</label><input class="form-control" name="preco_compra" type="number" step="0.01" value="{{ old('preco_compra', $produto->preco_compra ?? '') }}" required></div>
    <div class="col-md-3"><label class="form-label">Preço Venda</label><input class="form-control" name="preco_venda" type="number" step="0.01" value="{{ old('preco_venda', $produto->preco_venda ?? '') }}" required></div>
    <div class="col-md-3"><label class="form-label">Status</label><select class="form-select" name="status"><option value="disponivel">Disponível</option><option value="vendido" @selected(old('status', $produto->status ?? '')==='vendido')>Vendido</option></select></div>
    <div class="col-md-3"><label class="form-label">Gênero</label><select class="form-select" name="genero"><option value="">-</option><option value="masculino">Masculino</option><option value="feminino">Feminino</option><option value="unissex">Unissex</option></select></div>
    <div class="col-md-3"><label class="form-label">Categoria</label><select class="form-select" name="id_categoria"><option value="">-</option>@foreach($categorias as $item)<option value="{{ $item->id }}" @selected((string)old('id_categoria', $produto->id_categoria ?? '')===(string)$item->id)>{{ $item->nome }}</option>@endforeach</select></div>
    <div class="col-md-3"><label class="form-label">Cor</label><select class="form-select" name="id_cor"><option value="">-</option>@foreach($cores as $item)<option value="{{ $item->id }}" @selected((string)old('id_cor', $produto->id_cor ?? '')===(string)$item->id)>{{ $item->nome }}</option>@endforeach</select></div>
    <div class="col-md-3"><label class="form-label">Material</label><select class="form-select" name="id_material"><option value="">-</option>@foreach($materiais as $item)<option value="{{ $item->id }}" @selected((string)old('id_material', $produto->id_material ?? '')===(string)$item->id)>{{ $item->nome }}</option>@endforeach</select></div>
    <div class="col-md-3"><label class="form-label">Fornecedor</label><select class="form-select" name="id_fornecedor"><option value="">-</option>@foreach($fornecedores as $item)<option value="{{ $item->id }}" @selected((string)old('id_fornecedor', $produto->id_fornecedor ?? '')===(string)$item->id)>{{ $item->nome }}</option>@endforeach</select></div>
    <div class="col-12"><label class="form-label">Descrição</label><textarea class="form-control" name="descricao" rows="3">{{ old('descricao', $produto->descricao ?? '') }}</textarea></div>
</div>