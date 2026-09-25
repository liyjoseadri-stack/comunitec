<div class="campo-formulario">
    <label for="categoria_id">Categoría</label>
    <select id="categoria_id" name="categoria_id" required>
        <option value="">Selecciona</option>
        @foreach ($categorias as $categoria)
            <option value="{{ $categoria->id }}" @selected(old('categoria_id', $servicio?->categoria_id) == $categoria->id)>{{ $categoria->nombre }}</option>
        @endforeach
    </select>
</div>
<div class="campo-formulario">
    <label for="nombre">Nombre</label>
    <input id="nombre" name="nombre" value="{{ old('nombre', $servicio?->nombre) }}" required>
</div>
<div class="campo-formulario">
    <label for="codigo">Código</label>
    <input id="codigo" name="codigo" value="{{ old('codigo', $servicio?->codigo) }}" required>
</div>
<div class="campo-formulario campo-formulario--ancho">
    <label for="descripcion">Descripción o alcance</label>
    <textarea id="descripcion" name="descripcion">{{ old('descripcion', $servicio?->descripcion) }}</textarea>
</div>
<div class="campo-formulario">
    <label for="unidad">Unidad de cobro</label>
    <input id="unidad" name="unidad" value="{{ old('unidad', $servicio?->unidad ?? 'servicio') }}" required>
</div>
<div class="campo-formulario">
    <label for="precio">Precio con IVA</label>
    <input id="precio" type="number" step="0.01" min="0" name="precio" value="{{ old('precio', $servicio?->precio) }}" required>
</div>
