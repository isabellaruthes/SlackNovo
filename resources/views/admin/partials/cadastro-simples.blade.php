{{--
    Standard page for name-only registers (categorias, cores, materiais, fornecedores). SLK-09.
    Parameters:
      $titulo      page title (plural), e.g. "Categorias"
      $rotulo      singular label in lower case, e.g. "categoria"
      $novoRotulo  label of the add field, e.g. "Nova categoria"
      $rota        route prefix, e.g. "admin.categorias"
      $itens       paginator of models with id and nome
      $maximo      max length accepted by the controller
      $vazio       empty-state sentence, e.g. "Nenhuma categoria cadastrada."
    "_origem" tells which form failed validation, so only that field is highlighted.
--}}
@php($origem = old('_origem'))

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">{{ $titulo }}</h1>
    <span class="text-muted small">{{ $itens->total() }} {{ $itens->total() === 1 ? 'registro' : 'registros' }}</span>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form method="POST" action="{{ route($rota.'.store') }}">
            @csrf
            <input type="hidden" name="_origem" value="novo">
            @php($erroNovo = $origem === 'novo' && $errors->has('nome'))
            <label class="form-label" for="cadastro_novo_nome">{{ $novoRotulo }}</label>
            <div class="d-flex flex-wrap gap-2">
                <input class="form-control flex-grow-1 w-auto @if($erroNovo) is-invalid @endif" id="cadastro_novo_nome" name="nome" maxlength="{{ $maximo }}"
                    value="{{ $origem === 'novo' ? old('nome') : '' }}" required
                    @if($erroNovo) aria-invalid="true" aria-describedby="erro_cadastro_novo_nome" @endif>
                <button class="btn btn-primary">Adicionar</button>
                @if ($erroNovo)
                    <div class="invalid-feedback d-block w-100" id="erro_cadastro_novo_nome">{{ $errors->first('nome') }}</div>
                @endif
            </div>
        </form>
    </div>
</div>

@if ($itens->isEmpty())
    <div class="empty-state rounded p-4 text-center text-muted">
        {{ $vazio }} Use o formulário acima para adicionar.
    </div>
@else
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th scope="col">Nome</th>
                    <th scope="col" class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($itens as $item)
                    @php($campoId = 'cadastro_nome_'.$item->id)
                    @php($erroItem = $origem === 'editar-'.$item->id && $errors->has('nome'))
                    <tr>
                        <td>{{ $item->nome }}</td>
                        <td>
                            <div class="d-flex flex-wrap gap-2 justify-content-end align-items-start">
                                <form method="POST" action="{{ route($rota.'.update', $item) }}" class="d-flex flex-wrap gap-2">
                                    @csrf @method('PUT')
                                    <input type="hidden" name="_origem" value="editar-{{ $item->id }}">
                                    <label class="visually-hidden" for="{{ $campoId }}">Novo nome para {{ $item->nome }}</label>
                                    <div>
                                        <input class="form-control form-control-sm @if($erroItem) is-invalid @endif" id="{{ $campoId }}" name="nome" maxlength="{{ $maximo }}"
                                            value="{{ $erroItem ? old('nome') : $item->nome }}" required
                                            @if($erroItem) aria-invalid="true" aria-describedby="erro_{{ $campoId }}" @endif>
                                        @if ($erroItem)
                                            <div class="invalid-feedback" id="erro_{{ $campoId }}">{{ $errors->first('nome') }}</div>
                                        @endif
                                    </div>
                                    <button class="btn btn-sm btn-success">Salvar</button>
                                </form>
                                <form method="POST" action="{{ route($rota.'.destroy', $item) }}" class="js-confirm-delete" data-item-label="{{ $rotulo }} “{{ $item->nome }}”">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-danger">Excluir</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if ($itens->hasPages())
        <nav aria-label="Paginação de {{ mb_strtolower($titulo) }}">
            {{ $itens->links('pagination::bootstrap-5') }}
        </nav>
    @endif
@endif
