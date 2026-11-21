@props(['variante' => 'listado'])

<div
    id="esqueleto-carga"
    class="esqueleto-carga"
    data-esqueleto-carga
    data-variante="{{ $variante }}"
    hidden
    aria-hidden="true"
>
    <div class="esqueleto-carga__variante esqueleto-carga__variante--panel" data-esqueleto-variante="panel" aria-hidden="true">
        <div class="esqueleto-carga__encabezado">
            <span class="esqueleto-carga__bloque esqueleto-carga__bloque--titulo"></span>
            <span class="esqueleto-carga__bloque esqueleto-carga__bloque--subtitulo"></span>
        </div>
        <div class="esqueleto-carga__indicadores">
            <span class="esqueleto-carga__bloque esqueleto-carga__bloque--indicador"></span>
            <span class="esqueleto-carga__bloque esqueleto-carga__bloque--indicador"></span>
            <span class="esqueleto-carga__bloque esqueleto-carga__bloque--indicador"></span>
        </div>
        <div class="esqueleto-carga__tarjetas">
            <span class="esqueleto-carga__bloque esqueleto-carga__bloque--tarjeta"></span>
            <span class="esqueleto-carga__bloque esqueleto-carga__bloque--tarjeta"></span>
        </div>
    </div>

    <div class="esqueleto-carga__variante esqueleto-carga__variante--listado" data-esqueleto-variante="listado" aria-hidden="true">
        <div class="esqueleto-carga__encabezado">
            <span class="esqueleto-carga__bloque esqueleto-carga__bloque--titulo"></span>
            <span class="esqueleto-carga__bloque esqueleto-carga__bloque--subtitulo"></span>
        </div>
        <div class="esqueleto-carga__filtros">
            <span class="esqueleto-carga__bloque esqueleto-carga__bloque--campo"></span>
            <span class="esqueleto-carga__bloque esqueleto-carga__bloque--campo"></span>
            <span class="esqueleto-carga__bloque esqueleto-carga__bloque--boton"></span>
        </div>
        <div class="esqueleto-carga__tabla">
            @for ($fila = 0; $fila < 5; $fila++)
                <span class="esqueleto-carga__bloque esqueleto-carga__bloque--fila"></span>
            @endfor
        </div>
    </div>

    <div class="esqueleto-carga__variante esqueleto-carga__variante--formulario" data-esqueleto-variante="formulario" aria-hidden="true">
        <div class="esqueleto-carga__encabezado">
            <span class="esqueleto-carga__bloque esqueleto-carga__bloque--titulo"></span>
            <span class="esqueleto-carga__bloque esqueleto-carga__bloque--subtitulo"></span>
        </div>
        <div class="esqueleto-carga__formulario">
            @for ($campo = 0; $campo < 6; $campo++)
                <span class="esqueleto-carga__bloque esqueleto-carga__bloque--campo"></span>
            @endfor
        </div>
        <span class="esqueleto-carga__bloque esqueleto-carga__bloque--boton"></span>
    </div>
</div>
