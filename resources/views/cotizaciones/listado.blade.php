<!doctype html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Cotizaciones | Comunitec</title>
        <link rel="stylesheet" href="{{ asset('css/navegacion.css') }}">
        <link rel="stylesheet" href="{{ asset('css/administracion.css') }}">
    </head>
    <body>
        @include('componentes.navegacion')
        <main class="pagina-administrativa">
            <h1>
                Cotizaciones
            </h1>
            @if ($puedeEditar)
                <section class="bloque-administrativo" aria-labelledby="titulo-borrador">
                    <h2 id="titulo-borrador">Nueva cotización</h2>
                    @if ($customers->isEmpty())
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
                        <select id="cliente" name="customer_id" required>
                            <option value="">Selecciona un cliente</option>
                            @foreach ($customers as $c)
                                <option value="{{ $c->id }}" @selected(old('customer_id') == $c->id)>
                                    {{ $c->name }}
                                </option>
                            @endforeach
                        </select>
                        <label for="area">Área solicitante (opcional)</label>
                        <input id="area" name="area_requesting" maxlength="255" value="{{ old('area_requesting') }}">
                        <label for="descuento">Descuento (%)</label>
                        <input
                            id="descuento"
                            name="discount_percent"
                            type="number"
                            min="0"
                            max="10"
                            step="0.01"
                            value="{{ old('discount_percent', 0) }}"
                            aria-describedby="ayuda-descuento"
                        >
                        <p id="ayuda-descuento">Usa 0 sin descuento o un porcentaje entre 5 y 10.</p>
                        <button type="submit" @disabled($customers->isEmpty())>
                            Crear borrador
                        </button>
                    </form>
                </section>
            @endif
            <section class="bloque-administrativo" aria-labelledby="titulo-registradas">
                <h2 id="titulo-registradas">Cotizaciones registradas</h2>
                <ul class="listado-registros">
                    @forelse ($quotes as $q)
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
        </main>
    </body>
</html>
