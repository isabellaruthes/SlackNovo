<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Slacknovo')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>
    @auth
        @include('components.navbar')
    @endauth

    <main class="@yield('main_class', 'container py-3')">
        @yield('content')
    </main>

    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title fs-5">Confirmar exclusão</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0" id="confirmDeleteText">Tem certeza que deseja excluir este item?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteButton">Excluir</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const confirmModalElement = document.getElementById('confirmDeleteModal');
            if (!confirmModalElement) return;

            const modal = new bootstrap.Modal(confirmModalElement);
            const confirmButton = document.getElementById('confirmDeleteButton');
            const confirmText = document.getElementById('confirmDeleteText');
            let formToSubmit = null;

            document.querySelectorAll('form.js-confirm-delete').forEach(form => {
                form.addEventListener('submit', function (event) {
                    event.preventDefault();
                    formToSubmit = form;
                    const itemLabel = form.dataset.itemLabel ?? 'este item';
                    confirmText.textContent = `Tem certeza que deseja excluir ${itemLabel}?`;
                    modal.show();
                });
            });

            confirmButton.addEventListener('click', function () {
                if (formToSubmit) formToSubmit.submit();
            });
        });
    </script>
</body>

</html>
