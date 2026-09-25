@extends('layouts.aplicacion')

@section('titulo', 'Cotizaciones')

@section('contenido')
            <h1>
                Cotizaciones
            </h1>
            @if ($puedeEditar)
                <section class="bloque-administrativo" aria-labelledby="titulo-borrador">
                    <h2 id="titulo-borrador">Nueva cotización</h2>
                    @if ($clientes->isEmpty())
                        <p>
                            Primero registra un cliente para crear una cotización.
                            <a href="{{ route('clientes.listado') }}">Ir a Clientes</a>
                        </p>
                    @endif
                    @if ($errors->any())
                        <div role="alert">
                            <p>Revisa los datos de la cotización:</p>
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <form class="formulario-administrativo" method="post" action="{{ route('cotizaciones.guardar') }}">
                        @csrf
                        <label for="cliente">Cliente</label>
                        <select id="cliente" name="cliente_id" required>
                            <option value="">Selecciona un cliente</option>
                            @foreach ($clientes as $c)
                                <option value="{{ $c->id }}" @selected(old('cliente_id') == $c->id)>
                                    {{ $c->nombre }}
                                </option>
                            @endforeach
                        </select>
                        <label for="area">Área solicitante (opcional)</label>
                        <input id="area" name="area_solicitante" maxlength="255" value="{{ old('area_solicitante') }}">
                        <label for="descuento">Descuento (%)</label>
                        <input
                            id="descuento"
                            name="porcentaje_descuento"
                            type="number"
                            min="0"
                            max="10"
                            step="0.01"
                            value="{{ old('porcentaje_descuento', 0) }}"
                            aria-describedby="ayuda-descuento"
                        >
                        <p id="ayuda-descuento">Usa 0 sin descuento o un porcentaje entre 5 y 10.</p>
                        <button type="submit" @disabled($clientes->isEmpty())>
                            Crear borrador
                        </button>
                    </form>
                </section>
            @endif
            <section class="bloque-administrativo" aria-labelledby="titulo-registradas">
                <h2 id="titulo-registradas">Cotizaciones registradas</h2>
                <ul class="listado-registros">
                    @forelse ($cotizaciones as $q)
                        <li>
                            <a href="{{route('cotizaciones.detalle',$q)}}">
                                {{$q->folio}}
                            </a>
                            — {{ $q->etiquetaEstado() }}{{ $q->venta ? ' · Convertida en venta' : '' }}
                            — ${{ number_format((float) $q->total, 2) }}
                        </li>
                    @empty
                        <li>Aún no hay cotizaciones registradas.</li>
                    @endforelse
                </ul>
            </section>

@endsection
