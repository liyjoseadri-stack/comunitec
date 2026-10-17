@php($producto = $producto ?? null)

<x-campo-formulario id="categoria_id" nombre="categoria_id" etiqueta="Categoría" tipo="select" required>
    <option value="">Selecciona una categoría</option>
    @foreach ($categorias as $categoria)
        <option value="{{ $categoria->id }}" @selected(old('categoria_id', $producto?->categoria_id) == $categoria->id)>{{ $categoria->nombre }}</option>
    @endforeach
</x-campo-formulario>
<x-campo-formulario id="nombre" nombre="nombre" etiqueta="Nombre" :valor="old('nombre', $producto?->nombre)" required />
<x-campo-formulario id="codigo" nombre="codigo" etiqueta="Código/SKU" :valor="old('codigo', $producto?->codigo)" required />
<x-campo-formulario id="descripcion" nombre="descripcion" etiqueta="Descripción" tipo="textarea" contenedor="campo-formulario campo-formulario--ancho" :valor="old('descripcion', $producto?->descripcion)" />
<x-campo-formulario id="marca" nombre="marca" etiqueta="Marca" :valor="old('marca', $producto?->marca)" />
<x-campo-formulario id="modelo" nombre="modelo" etiqueta="Modelo" :valor="old('modelo', $producto?->modelo)" />
<x-campo-formulario id="unidad" nombre="unidad" etiqueta="Unidad" :valor="old('unidad', $producto?->unidad ?? 'pieza')" required />
<x-campo-formulario id="precio" nombre="precio" etiqueta="Precio con IVA" tipo="number" :valor="old('precio', $producto?->precio)" step="0.01" min="0" required />
<x-campo-formulario id="existencias" nombre="existencias" etiqueta="Existencias" tipo="number" :valor="old('existencias', $producto?->existencias ?? 0)" min="0" required />
@if ($producto)
    <x-campo-formulario
        id="motivo_existencias"
        nombre="motivo_existencias"
        etiqueta="Motivo del ajuste de existencias"
        tipo="textarea"
        contenedor="campo-formulario campo-formulario--ancho"
        :valor="old('motivo_existencias')"
        ayuda="Es obligatorio únicamente cuando cambias la cantidad de existencias."
    />
@endif
<div class="campo-formulario">
    <label class="control-verificacion" for="requiere-numero-serie">
        <input id="requiere-numero-serie" type="checkbox" name="requiere_numero_serie" value="1"
            @checked(old('requiere_numero_serie', $producto?->requiere_numero_serie ?? false))
            aria-describedby="requiere-numero-serie-ayuda{{ $errors->has('requiere_numero_serie') ? ' requiere-numero-serie-error' : '' }}"
            @if ($errors->has('requiere_numero_serie')) aria-invalid="true" @endif
        >
        <span>Requiere número de serie</span>
    </label>
    <small id="requiere-numero-serie-ayuda" class="ayuda-campo">Las series se capturan al registrar la venta.</small>
    @error('requiere_numero_serie')
        <p id="requiere-numero-serie-error" class="error-campo">{{ $message }}</p>
    @enderror
</div>
