@props([
    'nombre' => null,
    'etiqueta',
    'id' => null,
    'tipo' => 'text',
    'valor' => null,
    'ayuda' => null,
    'claveError' => null,
    'contenedor' => 'campo-formulario',
])

@php
    $id = $id ?? 'campo-'.Illuminate\Support\Str::uuid();
    $error = $errors->first($claveError ?? $nombre ?? '');
    $descripciones = array_filter([
        $attributes->get('aria-describedby'),
        $ayuda ? $id.'-ayuda' : null,
        $error ? $id.'-error' : null,
    ]);
    $control = $attributes->except(['aria-describedby', 'aria-invalid'])->merge([
        'id' => $id,
        'name' => $nombre,
        'aria-describedby' => $descripciones ? implode(' ', $descripciones) : null,
        'aria-invalid' => $error ? 'true' : null,
        'inputmode' => $tipo === 'number'
            ? (str_contains((string) $attributes->get('step', '1'), '.') || $attributes->get('step') === 'any' ? 'decimal' : 'numeric')
            : null,
    ]);
@endphp

<div class="{{ $contenedor }}">
    <label for="{{ $id }}">{{ $etiqueta }}</label>
    @if ($tipo === 'select')
        <select {{ $control }}>{{ $slot }}</select>
    @elseif ($tipo === 'textarea')
        <textarea {{ $control }}>{{ $valor }}</textarea>
    @elseif ($tipo === 'password')
        <input type="password" {{ $control }}>
    @else
        <input type="{{ $tipo }}" value="{{ $valor }}" {{ $control }}>
    @endif
    @if ($ayuda)
        <small id="{{ $id }}-ayuda" class="ayuda-campo">{{ $ayuda }}</small>
    @endif
    @if ($error)
        <p id="{{ $id }}-error" class="error-campo">{{ $error }}</p>
    @endif
</div>
