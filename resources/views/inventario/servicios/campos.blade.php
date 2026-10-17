<x-campo-formulario id="categoria_id" nombre="categoria_id" etiqueta="Categoría" tipo="select" required>
        <option value="">Selecciona</option>
        @foreach ($categorias as $categoria)
            <option value="{{ $categoria->id }}" @selected(old('categoria_id', $servicio?->categoria_id) == $categoria->id)>{{ $categoria->nombre }}</option>
        @endforeach

</x-campo-formulario>
<x-campo-formulario id="nombre" nombre="nombre" etiqueta="Nombre" :valor="old('nombre', $servicio?->nombre)" required />
<x-campo-formulario id="codigo" nombre="codigo" etiqueta="Código" :valor="old('codigo', $servicio?->codigo)" required />
<x-campo-formulario id="descripcion" nombre="descripcion" etiqueta="Descripción o alcance" tipo="textarea" contenedor="campo-formulario campo-formulario--ancho" :valor="old('descripcion', $servicio?->descripcion)" />
<x-campo-formulario id="unidad" nombre="unidad" etiqueta="Unidad de cobro" :valor="old('unidad', $servicio?->unidad ?? 'servicio')" required />
<x-campo-formulario id="precio" nombre="precio" etiqueta="Precio con IVA" tipo="number" :valor="old('precio', $servicio?->precio)" step="0.01" min="0" required />
