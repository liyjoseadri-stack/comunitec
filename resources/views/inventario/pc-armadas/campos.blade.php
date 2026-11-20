@php
    $componentesFormulario = old('componentes', isset($pcArmada) ? $pcArmada->componentes->map(fn ($componente) => [
        'producto_id' => $componente->producto_id,
        'tipo_componente_pc' => $componente->tipo_componente_pc,
        'cantidad' => $componente->cantidad,
    ])->all() : []);
    $componentesPorTipo = collect($componentesFormulario)->groupBy('tipo_componente_pc');
    $apartadosPresentacion = collect($tiposComponentesPc);
@endphp

<div class="rejilla-formulario">
    <x-campo-formulario id="nombre" nombre="nombre" etiqueta="Nombre de la PC armada" requerido :valor="old('nombre', $pcArmada->nombre ?? '')" />
    <x-campo-formulario id="descripcion" nombre="descripcion" etiqueta="Descripción" tipo="textarea" :valor="old('descripcion', $pcArmada->descripcion ?? '')" />
</div>

<div class="encabezado-seccion">
    <div>
        <h3>Componentes de la plantilla</h3>
        <p class="texto-ayuda">Cada apartado ya está separado. Selecciona los productos del inventario y agrega otro solo cuando necesites más de uno en la misma categoría.</p>
    </div>
</div>

<div class="pc-armada-apartados" data-formulario-pc-armada>
    @foreach ($apartadosPresentacion as $tipo => $etiqueta)
        @php
            $tiposPermitidos = [$tipo];
            $componentes = $componentesPorTipo->only($tiposPermitidos)->flatMap(fn ($grupo) => $grupo)->values();
            if ($componentes->isEmpty()) {
                $componentes = collect([[]]);
            }
        @endphp
        <fieldset class="pc-armada-apartado" data-seccion-pc="{{ $tipo }}" data-tipos-pc="{{ implode(',', $tiposPermitidos) }}">
            <legend>{{ $etiqueta }}</legend>
            <p class="texto-ayuda">{{ $tipo === 'accesorio' ? 'Monitores, kits de teclado y mouse, bocinas, adaptadores y otros complementos.' : 'Productos clasificados como '.strtolower($etiqueta).'.' }}</p>
            <div class="pc-armada-apartado__filas" data-componentes>
                @foreach ($componentes as $componente)
                    @php($indice = $loop->parent->index.'-'.$loop->index)
                    <div class="pc-armada-apartado__fila" data-componente>
                        <input type="hidden" name="componentes[{{ $indice }}][tipo_componente_pc]" value="{{ $tipo }}">
                        <div class="campo-formulario">
                            <label for="producto-{{ $indice }}">Producto</label>
                            <select id="producto-{{ $indice }}" name="componentes[{{ $indice }}][producto_id]" data-producto-componente data-producto-inicial="{{ $componente['producto_id'] ?? '' }}">
                                <option value="">No agregar por ahora</option>
                                @foreach ($productos as $producto)
                                    <option value="{{ $producto->id }}" data-tipo="{{ $producto->tipo_componente_pc }}" @selected((string) ($componente['producto_id'] ?? '') === (string) $producto->id)>{{ $producto->nombre }} ({{ $producto->codigo }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="campo-formulario campo-formulario--cantidad">
                            <label for="cantidad-{{ $indice }}">Cantidad</label>
                            <input id="cantidad-{{ $indice }}" type="number" name="componentes[{{ $indice }}][cantidad]" min="1" step="1" value="{{ $componente['cantidad'] ?? 1 }}" data-cantidad-componente>
                        </div>
                        <button class="boton boton--peligro boton--compacto" type="button" data-eliminar-componente>Quitar</button>
                    </div>
                @endforeach
            </div>
            <button class="boton boton--contorno boton--compacto" type="button" data-agregar-componente>+ Agregar otro producto</button>
        </fieldset>
    @endforeach
</div>

@push('scripts')
    <script src="{{ asset('js/pc-armadas.js') }}?v={{ filemtime(public_path('js/pc-armadas.js')) }}" defer></script>
@endpush
