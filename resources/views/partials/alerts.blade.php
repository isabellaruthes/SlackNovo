{{-- Standard feedback: success (status), business error and validation errors (alert).
     Field-level messages are shown next to each field by <x-erro-campo>. --}}
@if (session('success'))
    <div class="alert alert-success d-flex gap-2 align-items-start" role="status">
        <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
        <span>{{ session('success') }}</span>
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger d-flex gap-2 align-items-start" role="alert">
        <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
        <span>{{ session('error') }}</span>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger d-flex gap-2 align-items-start" role="alert">
        <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
        <div>
            <p class="mb-1 fw-semibold">Revise os dados informados:</p>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
