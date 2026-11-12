@props(['status'])
{{-- Standard product status badge (SLK-09): same wording and colour on every screen. --}}
@php
    [$texto, $classe] = match ($status) {
        'disponivel' => ['Disponível', 'text-bg-success'],
        'vendido' => ['Vendido', 'text-bg-secondary'],
        default => [ucfirst((string) $status), 'text-bg-light'],
    };
@endphp
<span {{ $attributes->class(['badge', $classe]) }}>{{ $texto }}</span>
