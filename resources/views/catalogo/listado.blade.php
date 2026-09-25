@extends('layouts.aplicacion')

@section('titulo', 'Catálogo')

@section('contenido')
            <header class="encabezado-pagina">
                <div>
                    <h1>Catálogo</h1>
                    <p>Administra los productos y servicios disponibles para las cotizaciones.</p>
                </div>
                <span class="contador-registros">
                    {{ $articulos->count() }} {{ $articulos->count() === 1 ? 'concepto' : 'conceptos' }}
                </span>
            </header>

            @if ($errors->any())
                <div role="alert" aria-labelledby="titulo-errores-catalogo">
                    <strong id="titulo-errores-catalogo">Revisa la información del concepto:</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('success'))
                <p class="mensaje-exito" role="status">{{ session('success') }}</p>
            @endif

            <section class="bloque-administrativo" aria-labelledby="titulo-nuevo-concepto">
                <div class="encabezado-seccion">
                    <div>
                        <h2 id="titulo-nuevo-concepto">Registrar concepto</h2>
                        <p>Los campos marcados con <span aria-hidden="true">*</span> son obligatorios.</p>
                    </div>
                </div>

                <form class="formulario-administrativo formulario-catalogo" method="POST" action="{{ route('catalogo.guardar') }}">
                    @csrf

                    <div class="campo-formulario">
                        <label for="tipo">Tipo de concepto</label>
                        <select id="tipo" name="tipo" required>
                            <option value="producto" @selected(old('tipo', 'producto') === 'producto')>Producto</option>
                            <option value="servicio" @selected(old('tipo') === 'servicio')>Servicio</option>
                        </select>
                    </div>

                    <div class="campo-formulario">
                        <label for="categoria">Categoría <span class="texto-opcional">(opcional)</span></label>
                        <select id="categoria" name="categoria_id">
                            <option value="">Sin categoría</option>
                            @foreach ($categorias as $categoria)
                                <option value="{{ $categoria->id }}" @selected(old('categoria_id') == $categoria->id)>
                                    {{ $categoria->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="campo-formulario campo-formulario--ancho">
                        <label for="nombre">Nombre <span aria-hidden="true">*</span></label>
                        <input id="nombre" name="nombre" value="{{ old('nombre') }}" autocomplete="off" required>
                    </div>

                    <div class="campo-formulario">
                        <label for="codigo">Código <span aria-hidden="true">*</span></label>
                        <input id="codigo" name="codigo" value="{{ old('codigo') }}" autocomplete="off" required>
                    </div>

                    <div class="campo-formulario">
                        <label for="unidad">Unidad <span aria-hidden="true">*</span></label>
                        <input id="unidad" name="unidad" value="{{ old('unidad') }}" placeholder="Ej. pieza, servicio o metro" required>
                    </div>

                    <div class="campo-formulario">
                        <label for="marca">Marca <span class="texto-opcional">(opcional)</span></label>
                        <input id="marca" name="marca" value="{{ old('marca') }}">
                    </div>

                    <div class="campo-formulario">
                        <label for="modelo">Modelo <span class="texto-opcional">(opcional)</span></label>
                        <input id="modelo" name="modelo" value="{{ old('modelo') }}">
                    </div>

                    <div class="campo-formulario">
                        <label for="precio">Precio con IVA</label>
                        <div class="entrada-con-prefijo">
                            <span aria-hidden="true">$</span>
                            <input id="precio" name="precio" type="number" min="0" step="0.01" value="{{ old('precio') }}" inputmode="decimal" required>
                        </div>
                    </div>

                    <div class="campo-formulario">
                        <label for="existencias">Existencias iniciales</label>
                        <input id="existencias" name="existencias" type="number" min="0" step="1" value="{{ old('existencias') }}" inputmode="numeric" aria-describedby="ayuda-existencias">
                        <small id="ayuda-existencias">Obligatorio únicamente para productos.</small>
                    </div>

                    <div class="acciones-formulario campo-formulario--ancho">
                        <button type="submit">Guardar concepto</button>
                    </div>
                </form>
            </section>

            <section class="bloque-administrativo" aria-labelledby="titulo-conceptos">
                <div class="encabezado-seccion">
                    <div>
                        <h2 id="titulo-conceptos">Conceptos registrados</h2>
                        <p>Precios mostrados con IVA incluido.</p>
                    </div>
                </div>

                <div class="contenedor-tabla" tabindex="0" aria-label="Tabla de conceptos registrados">
                    <table class="tabla-registros tabla-catalogo">
                        <thead>
                            <tr>
                                <th scope="col">Código</th>
                                <th scope="col">Nombre</th>
                                <th scope="col">Categoría</th>
                                <th scope="col">Tipo</th>
                                <th scope="col">Unidad</th>
                                <th scope="col">Precio</th>
                                <th scope="col">Existencias</th>
                                <th scope="col">Estado</th>
                                <th scope="col">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($articulos as $articulo)
                                <tr>
                                    <td><strong>{{ $articulo->codigo }}</strong></td>
                                    <td>
                                        {{ $articulo->nombre }}
                                        @if ($articulo->marca || $articulo->modelo)
                                            <small class="detalle-tabla">
                                                {{ collect([$articulo->marca, $articulo->modelo])->filter()->join(' · ') }}
                                            </small>
                                        @endif
                                    </td>
                                    <td>{{ $articulo->categoria?->nombre ?? 'Sin categoría' }}</td>
                                    <td>
                                        <span class="insignia-tipo">
                                            {{ $articulo->tipo === 'producto' ? 'Producto' : 'Servicio' }}
                                        </span>
                                    </td>
                                    <td>{{ $articulo->unidad }}</td>
                                    <td>${{ number_format((float) $articulo->precio, 2) }}</td>
                                    <td>{{ $articulo->tipo === 'producto' ? $articulo->existencias : 'No aplica' }}</td>
                                    <td>{{ $articulo->activo ? 'Activo' : 'Inactivo' }}</td>
                                    <td>
                                        <form method="POST" action="{{ route('catalogo.estado', $articulo) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit">
                                                {{ $articulo->activo ? 'Desactivar' : 'Reactivar' }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="estado-vacio" colspan="9">
                                        Aún no hay productos ni servicios registrados.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

@endsection
