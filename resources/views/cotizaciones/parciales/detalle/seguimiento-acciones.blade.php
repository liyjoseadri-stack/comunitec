@php
    $seguimientoConcluido = in_array($cotizacion->estado, [
        \App\Models\Cotizacion::ESTADO_RECHAZADA,
        \App\Models\Cotizacion::ESTADO_CANCELADA,
        \App\Models\Cotizacion::ESTADO_VENCIDA,
        \App\Models\Cotizacion::ESTADO_VENTA,
    ], true);

    $descripcionSeguimiento = match ($cotizacion->estado) {
        \App\Models\Cotizacion::ESTADO_BORRADOR => 'Envía la cotización para iniciar su vigencia y compartirla con el cliente.',
        \App\Models\Cotizacion::ESTADO_PENDIENTE => 'Registra la respuesta del cliente antes de que termine la vigencia.',
        \App\Models\Cotizacion::ESTADO_ACEPTADA => 'El método de pago y los números de serie se registran en el módulo Ventas.',
        \App\Models\Cotizacion::ESTADO_RECHAZADA => 'Esta cotización fue rechazada.',
        \App\Models\Cotizacion::ESTADO_CANCELADA => 'Esta cotización fue cancelada.',
        \App\Models\Cotizacion::ESTADO_VENCIDA => 'La vigencia de esta cotización terminó sin una aceptación.',
        \App\Models\Cotizacion::ESTADO_VENTA => 'Esta cotización ya fue convertida en venta.',
        default => 'Consulta el estado actual antes de continuar.',
    };
@endphp

<section
    class="panel-seguimiento-cotizacion"
    aria-labelledby="{{ $seguimientoConcluido ? 'titulo-seguimiento-concluido' : 'titulo-siguiente-paso' }}"
>
    <div class="panel-seguimiento-cotizacion__encabezado">
        <div>
            <p class="panel-seguimiento-cotizacion__etiqueta">Seguimiento</p>
            <h2 id="{{ $seguimientoConcluido ? 'titulo-seguimiento-concluido' : 'titulo-siguiente-paso' }}">
                {{ $seguimientoConcluido ? 'Seguimiento concluido' : 'Siguiente paso' }}
            </h2>
        </div>
        <x-insignia-estado :estado="$cotizacion->estado" :etiqueta="$cotizacion->etiquetaEstado()" />
    </div>

    <p class="panel-seguimiento-cotizacion__descripcion">{{ $descripcionSeguimiento }}</p>

    @if ($puedeEditar && $cotizacion->estado === \App\Models\Cotizacion::ESTADO_BORRADOR)
        <div class="accion-principal-cotizacion" data-accion-principal>
            <form class="formulario-administrativo" method="post" action="{{ route('cotizaciones.enviar', $cotizacion) }}">
                @csrf
                <input type="hidden" name="token_envio" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                <x-boton tipo="submit">Enviar cotización</x-boton>
            </form>
        </div>

        <div class="acciones-adicionales-cotizacion" data-zona-destructiva aria-labelledby="titulo-acciones-adicionales">
            <h3 id="titulo-acciones-adicionales">Acciones adicionales</h3>
            <form class="formulario-administrativo" method="post" action="{{ route('cotizaciones.cancelar', $cotizacion) }}"
                data-confirmar data-confirmar-titulo="Cancelar cotización"
                data-confirmar-mensaje="La cotización {{ $cotizacion->folio }} quedará cancelada y no podrá enviarse ni convertirse en venta."
                data-confirmar-etiqueta="Cancelar cotización">
                @csrf
                <x-boton variante="peligro" tipo="submit">Cancelar cotización</x-boton>
            </form>
        </div>
    @elseif ($puedeEditar && $cotizacion->estado === \App\Models\Cotizacion::ESTADO_PENDIENTE)
        <div class="accion-principal-cotizacion" data-accion-principal>
            <form class="formulario-administrativo" method="post" action="{{ route('cotizaciones.aceptar', $cotizacion) }}">
                @csrf
                <x-boton variante="exito" tipo="submit">Aceptar cotización</x-boton>
            </form>
        </div>

        <div class="acciones-adicionales-cotizacion" data-zona-destructiva aria-labelledby="titulo-acciones-adicionales">
            <h3 id="titulo-acciones-adicionales">Acciones adicionales</h3>
            <div class="grupo-acciones acciones-cotizacion">
                <form class="formulario-administrativo" method="post" action="{{ route('cotizaciones.correo', $cotizacion) }}">
                    @csrf
                    <input type="hidden" name="token_envio" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                    <x-boton variante="contorno" tipo="submit">Enviar por correo</x-boton>
                </form>
                <form class="formulario-administrativo" method="post" action="{{ route('cotizaciones.rechazar', $cotizacion) }}"
                    data-confirmar data-confirmar-titulo="Rechazar cotización"
                    data-confirmar-mensaje="La cotización {{ $cotizacion->folio }} quedará rechazada y no podrá aceptarse ni convertirse en venta."
                    data-confirmar-etiqueta="Rechazar cotización">
                    @csrf
                    <x-boton variante="peligro" tipo="submit">Rechazar cotización</x-boton>
                </form>
                <form class="formulario-administrativo" method="post" action="{{ route('cotizaciones.cancelar', $cotizacion) }}"
                    data-confirmar data-confirmar-titulo="Cancelar cotización"
                    data-confirmar-mensaje="La cotización {{ $cotizacion->folio }} quedará cancelada y no podrá aceptarse ni convertirse en venta."
                    data-confirmar-etiqueta="Cancelar cotización">
                    @csrf
                    <x-boton variante="peligro" tipo="submit">Cancelar cotización</x-boton>
                </form>
            </div>
        </div>
    @elseif ($puedeEditar && $cotizacion->estado === \App\Models\Cotizacion::ESTADO_ACEPTADA)
        <div class="accion-principal-cotizacion" data-accion-principal>
            <x-boton :href="route('ventas.crear', $cotizacion)">Continuar en Ventas</x-boton>
        </div>

        <div class="acciones-adicionales-cotizacion" data-zona-destructiva aria-labelledby="titulo-acciones-adicionales">
            <h3 id="titulo-acciones-adicionales">Acciones adicionales</h3>
            <p class="ayuda-campo">
                Guardar un cambio libera las piezas reservadas y devuelve la cotización a Pendiente para una nueva aceptación.
            </p>
            <form class="formulario-administrativo" method="post" action="{{ route('cotizaciones.cancelar', $cotizacion) }}"
                data-confirmar data-confirmar-titulo="Cancelar y liberar reservas"
                data-confirmar-mensaje="La cotización {{ $cotizacion->folio }} quedará cancelada y se liberarán sus reservas de inventario. No podrá convertirse en venta."
                data-confirmar-etiqueta="Cancelar y liberar reservas">
                @csrf
                <x-boton variante="peligro" tipo="submit">Cancelar y liberar piezas</x-boton>
            </form>
        </div>
    @endif
</section>
