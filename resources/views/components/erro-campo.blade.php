@props(['campo', 'id' => null, 'bloco' => false])
{{-- Field-level validation message. Pair with the field's is-invalid class and aria-describedby.
     "bloco" forces display for groups (radios) that have no single .is-invalid sibling. --}}
@error($campo)
    <div @class(['invalid-feedback', 'd-block' => $bloco]) id="{{ $id ?? 'erro_'.$campo }}">{{ $message }}</div>
@enderror
