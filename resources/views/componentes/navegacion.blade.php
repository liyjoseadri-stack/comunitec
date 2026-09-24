@php
    $enlaces = [
        ['ruta' => 'panel', 'patron' => 'panel', 'texto' => 'Panel'],
        ['ruta' => 'cotizaciones.listado', 'patron' => 'cotizaciones.*', 'texto' => 'Cotizaciones'],
        ['ruta' => 'ventas.listado', 'patron' => 'ventas.*', 'texto' => 'Ventas'],
        ['ruta' => 'reportes.cotizaciones', 'patron' => 'reportes.*', 'texto' => 'Reportes'],
    ];

    if (auth()->user()->esAdministrador() || auth()->user()->esComercial()) {
        $enlaces[] = ['ruta' => 'clientes.listado', 'patron' => 'clientes.*', 'texto' => 'Clientes'];
        $enlaces[] = ['ruta' => 'catalogo.listado', 'patron' => 'catalogo.*', 'texto' => 'Catálogo'];
        $enlaces[] = ['ruta' => 'inventario.listado', 'patron' => 'inventario.*', 'texto' => 'Inventario'];
    }

    if (auth()->user()->esAdministrador()) {
        $enlaces[] = [
            'ruta' => 'administracion.usuarios.listado',
            'patron' => 'administracion.usuarios.*',
            'texto' => 'Usuarios',
        ];
    }
@endphp

<nav class="navegacion-principal" aria-label="Navegación principal">
    <span class="navegacion-principal__marca">Comunitec</span>
    <ul>
        @foreach ($enlaces as $enlace)
            <li>
                <a
                    href="{{ route($enlace['ruta']) }}"
                    @if (request()->routeIs($enlace['patron'])) aria-current="page" @endif
                >
                    {{ $enlace['texto'] }}
                </a>
            </li>
        @endforeach
    </ul>
</nav>
