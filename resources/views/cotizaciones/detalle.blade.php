<!doctype html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>
            {{ $quote->folio }} | Comunitec
        </title>
        <link rel="stylesheet" href="{{ asset('css/navegacion.css') }}">
        <link rel="stylesheet" href="{{ asset('css/administracion.css') }}">
    </head>
    <body>
        @include('componentes.navegacion')
        <main class="pagina-administrativa detalle-cotizacion">
            <h1>
                Cotización {{ $quote->folio }}
            </h1>
            @if ($errors->any())
                <ul role="alert">
                    @foreach ($errors->all() as $error)
                        <li>
                            {{ $error }}
                        </li>
                    @endforeach
                </ul>
            @endif
            @if (session('success'))
                <p role="status">
                    {{ session('success') }}
                </p>
            @endif
            @if (session('error'))
                <p role="alert">
                    {{ session('error') }}
                </p>
            @endif
            <p>
                Cliente:
                <strong>
                    {{ $quote->customer->name }}
                </strong>
            </p>
            <p>
                Estado:
                <strong>
                    {{ $quote->etiquetaEstado() }}
                </strong>
            </p>
            @if ($quote->area_requesting)
                <p>
                    Área solicitante: {{ $quote->area_requesting }}
                </p>
            @endif
            @if (in_array($quote->status, ['draft', 'pending'], true) && $shortages->isNotEmpty())
                <section role="alert">
                    <h2>
                        Advertencia de inventario
                    </h2>
                    <ul>
                        @foreach ($shortages as $shortage)
                            <li>
                                {{ $shortage['description'] }}: se cotizaron {{ $shortage['requested'] }} piezas y hay {{ $shortage['available'] }} disponibles.
                            </li>
                        @endforeach
                    </ul>
                    <p>
                        El borrador puede guardarse, pero no podrá aceptarse hasta contar con las piezas necesarias.
                    </p>
                </section>
            @endif
            <p>
                Descuento global: {{ $quote->discount_percent }}%
            </p>
            @if ($puedeEditar && in_array($quote->status, ['draft', 'pending', 'accepted'], true))
                <details>
                    <summary>
                        Editar datos de la cotización
                    </summary>
                    <form class="formulario-administrativo" method="post" action="{{ route('cotizaciones.actualizar', $quote) }}">
                        @csrf
                        @method('PUT')
                        <label>
                            Cliente
                            <select name="customer_id" required>
                                @foreach ($clientes as $cliente)
                                    <option value="{{ $cliente->id }}" @selected(old('customer_id', $quote->
                                        customer_id) == $cliente->id)>{{ $cliente->name }}
                                    </option>
                                @endforeach
                            </select>
                        </label>
                        <label>
                            Área solicitante
                            <input name="area_requesting" value="{{ old('area_requesting', $quote->area_requesting) }}" maxlength="255">
                        </label>
                        <label>
                            Descuento global (%)
                            <input type="number" name="discount_percent" value="{{ old('discount_percent', $quote->discount_percent) }}" min="0" max="10" step="0.01">
                        </label>
                        <p>
                            Usa 0 para no aplicar descuento, o un porcentaje entre 5 y 10.
                        </p>
                        <button type="submit">
                            Guardar datos de la cotización
                        </button>
                    </form>
                </details>
            @endif
            <p>
                <a href="{{ route('cotizaciones.pdf', $quote) }}">
                    Descargar PDF
                </a>
            </p>
            @if ($quote->enviosCorreo->isNotEmpty())
                <section class="bloque-administrativo" aria-labelledby="titulo-historial-correo">
                    <h2 id="titulo-historial-correo">
                        Historial de correo
                    </h2>
                    <p>
                        Cada registro indica si el servicio de correo aceptó el mensaje. La aceptación técnica no confirma que el cliente lo haya leído.
                    </p>
                    <div class="contenedor-tabla" tabindex="0" aria-label="Historial de envíos por correo">
                        <table class="tabla-registros">
                            <thead>
                                <tr>
                                    <th scope="col">
                                        Fecha
                                    </th>
                                    <th scope="col">
                                        Destinatario
                                    </th>
                                    <th scope="col">
                                        Resultado
                                    </th>
                                    <th scope="col">
                                        Usuario
                                    </th>
                                    <th scope="col">
                                        Detalle
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($quote->enviosCorreo as $envio)
                                    <tr>
                                        <td>
                                            {{ $envio->attempted_at->format('d/m/Y H:i') }}
                                        </td>
                                        <td>
                                            {{ $envio->recipient }}
                                        </td>
                                        <td>
                                            {{ $envio->etiquetaResultado() }}
                                        </td>
                                        <td>
                                            {{ $envio->usuario?->name ?? 'Usuario no disponible' }}
                                        </td>
                                        <td>
                                            {{ $envio->message }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif
            @if ($puedeEditar && $quote->status === 'draft')
                <form class="formulario-administrativo" method="post" action="{{ route('cotizaciones.enviar', $quote) }}">
                    @csrf
                    <button type="submit">
                        Enviar cotización
                    </button>
                </form>
                <form class="formulario-administrativo" method="post" action="{{ route('cotizaciones.cancelar', $quote) }}">
                    @csrf
                    <button type="submit">
                        Cancelar cotización
                    </button>
                </form>
            @elseif ($puedeEditar && $quote->status === 'pending')
                <form class="formulario-administrativo" method="post" action="{{ route('cotizaciones.correo', $quote) }}">
                    @csrf
                    <button type="submit">
                        Enviar por correo al cliente
                    </button>
                </form>
                <form class="formulario-administrativo" method="post" action="{{ route('cotizaciones.aceptar', $quote) }}">
                    @csrf
                    <button type="submit">
                        Aceptar cotización
                    </button>
                </form>
                <form class="formulario-administrativo" method="post" action="{{ route('cotizaciones.rechazar', $quote) }}">
                    @csrf
                    <button type="submit">
                        Rechazar cotización
                    </button>
                </form>
                <form class="formulario-administrativo" method="post" action="{{ route('cotizaciones.cancelar', $quote) }}">
                    @csrf
                    <button type="submit">
                        Cancelar cotización
                    </button>
                </form>
            @endif
            @if ($puedeEditar && $quote->status === 'accepted')
                <p>
                    Guardar un cambio libera las piezas reservadas y devuelve la cotización a Pendiente para una nueva aceptación.
                </p>
                <form class="formulario-administrativo" method="post" action="{{ route('cotizaciones.cancelar', $quote) }}">
                    @csrf
                    <button type="submit">
                        Cancelar y liberar piezas
                    </button>
                </form>
            @endif
            @if ($puedeEditar && in_array($quote->status, ['draft', 'pending', 'accepted'], true))
                <h2>
                    Agregar partida
                </h2>
                <form class="formulario-administrativo" method="post" action="{{ route('cotizaciones.partidas.guardar', $quote) }}">
                    @csrf
                    <label for="tipo-partida">Tipo de partida</label>
                    <select id="tipo-partida" name="type">
                        <option value="product">
                            Producto
                        </option>
                        <option value="service">
                            Servicio
                        </option>
                        <option value="other">
                            Otro
                        </option>
                    </select>
                    <label for="articulo-partida">Artículo del catálogo</label>
                    <select id="articulo-partida" name="catalog_item_id">
                        <option value="">
                            Concepto libre
                        </option>
                        @foreach ($items as $item)
                            <option value="{{ $item->id }}">
                                {{ $item->name }} — {{ $item->type === 'product' ? 'Producto' : 'Servicio' }} — ${{ $item->price }}
                            </option>
                        @endforeach
                    </select>
                    <p>Productos y servicios usan el precio vigente del catálogo. La descripción permite precisar lo cotizado.</p>
                    <label for="descripcion-partida">Descripción</label>
                    <input id="descripcion-partida" name="description" value="{{ old('description') }}" required>
                    <label for="cantidad-partida">Cantidad</label>
                    <input id="cantidad-partida" name="quantity" type="number" step="0.01" min="0.01" required>
                    <p>Los productos requieren cantidades enteras de piezas.</p>
                    <label for="precio-partida">Precio unitario con IVA (solo para Otro)</label>
                    <input id="precio-partida" name="unit_price" type="number" step="0.01" min="0" value="{{ old('unit_price') }}">
                    <button type="submit">
                        Agregar partida
                    </button>
                </form>
            @endif
            <h2>
                Partidas
            </h2>
            <div class="contenedor-partidas" role="region" aria-label="Partidas de la cotización" tabindex="0">
                <table class="tabla-registros tabla-partidas">
                    <thead>
                        <tr>
                            <th scope="col">
                                Descripción
                            </th>
                            <th scope="col">
                                Cantidad
                            </th>
                            <th scope="col">
                                Precio
                            </th>
                            <th scope="col">
                                Subtotal
                            </th>
                            <th scope="col">
                                Acciones
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($quote->lines as $line)
                            @if ($puedeEditar && in_array($quote->status, ['draft', 'pending', 'accepted'], true))
                                <tr>
                                    <td colspan="5">
                                        <details>
                                            <summary>Editar partida: {{ $line->description }}</summary>
                                            <form class="formulario-administrativo" method="post" action="{{ route('cotizaciones.partidas.actualizar', [$quote, $line]) }}">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="type" value="{{ $line->type }}">
                                                <input type="hidden" name="catalog_item_id" value="{{ $line->catalog_item_id }}">
                                                <label>
                                                    Descripción
                                                    <input
                                                        name="description"
                                                        value="{{ $line->description }}"
                                                        required
                                                    >
                                                </label>
                                                <label>
                                                    Cantidad
                                                    <input
                                                        type="number"
                                                        name="quantity"
                                                        value="{{ $line->quantity }}"
                                                        min="{{ $line->type === 'product' ? '1' : '0.01' }}"
                                                        step="{{ $line->type === 'product' ? '1' : '0.01' }}"
                                                        required
                                                    >
                                                </label>
                                                <label>
                                                    Precio unitario
                                                    <input
                                                        type="number"
                                                        name="unit_price"
                                                        value="{{ $line->unit_price }}"
                                                        min="0"
                                                        step="0.01"
                                                        @readonly($line->type !== 'other')
                                                        required
                                                    >
                                                </label>
                                                <button type="submit">
                                                    Guardar cambios
                                                </button>
                                            </form>
                                        </details>
                                    </td>
                                </tr>
                            @endif
                            <tr>
                                <td>
                                    {{ $line->description }}
                                </td>
                                <td>
                                    {{ $line->quantity }}
                                </td>
                                <td>
                                    ${{ $line->unit_price }}
                                </td>
                                <td>
                                    ${{ $line->subtotal }}
                                </td>
                                <td>
                                    @if ($puedeEditar && $quote->status === 'draft')
                                        <form class="formulario-administrativo" method="post" action="{{ route('cotizaciones.partidas.eliminar', [$quote, $line]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit">
                                                Quitar
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p>
                <strong>
                    Total: ${{ $quote->total }}
                </strong>
            </p>
        </main>
    </body>
</html>
