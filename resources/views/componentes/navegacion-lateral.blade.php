@php
    $usuarioNavegacion = auth()->user();
    $puedeOperar = $usuarioNavegacion->esAdministrador() || $usuarioNavegacion->esComercial();
    $cotizacionesActivas = request()->routeIs('cotizaciones.*');
    $ventasActivas = request()->routeIs('ventas.*');
    $inventarioActivo = request()->routeIs('inventario.*', 'catalogo.*');
    $reportesActivos = request()->routeIs('reportes.*');
    $administracionActiva = request()->routeIs('administracion.*');
@endphp

<aside id="navegacion-lateral" class="navegacion-lateral" aria-label="Navegación administrativa">
    <div class="navegacion-lateral__marca">
        <a href="{{ route('panel') }}" aria-label="Ir al panel de COMUN&TEC">
            <img
                src="{{ asset('images/logo-comunitec-transparente.png') }}"
                alt="COMUN&TEC"
                width="1469"
                height="917"
            >
        </a>
    </div>

    <nav class="menu-administrativo" aria-label="Módulos del sistema">
        <a
            class="menu-administrativo__enlace"
            href="{{ route('panel') }}"
            @if (request()->routeIs('panel')) aria-current="page" @endif
        >
            <x-icono nombre="inicio" />
            <span>Inicio</span>
        </a>

        <details data-grupo="cotizaciones" @if ($cotizacionesActivas) open @endif>
            <summary>
                <span><x-icono nombre="cotizaciones" /> Cotizaciones</span>
                <x-icono nombre="flecha" />
            </summary>
            <div class="menu-administrativo__subgrupo">
                @if ($puedeOperar)
                    <a href="{{ route('cotizaciones.listado') }}#nueva-cotizacion">Nueva cotización</a>
                @endif
                <a
                    href="{{ route('cotizaciones.listado') }}"
                    @if ($cotizacionesActivas && ! request()->filled('estado')) aria-current="page" @endif
                >Historial de cotizaciones</a>
            </div>
        </details>

        <details data-grupo="ventas" @if ($ventasActivas) open @endif>
            <summary>
                <span><x-icono nombre="ventas" /> Ventas</span>
                <x-icono nombre="flecha" />
            </summary>
            <div class="menu-administrativo__subgrupo">
                @if ($puedeOperar)
                    <a href="{{ route('ventas.listado') }}#cotizaciones-aceptadas">Registrar venta</a>
                @endif
                <a
                    href="{{ route('ventas.listado') }}"
                    @if ($ventasActivas) aria-current="page" @endif
                >Historial de ventas</a>
            </div>
        </details>

        @if ($puedeOperar)
            <a
                class="menu-administrativo__enlace"
                href="{{ route('clientes.listado') }}"
                @if (request()->routeIs('clientes.*')) aria-current="page" @endif
            >
                <x-icono nombre="clientes" />
                <span>Clientes</span>
            </a>

            <details data-grupo="inventario" @if ($inventarioActivo) open @endif>
                <summary>
                    <span><x-icono nombre="inventario" /> Inventario</span>
                    <x-icono nombre="flecha" />
                </summary>
                <div class="menu-administrativo__subgrupo">
                    <a
                        href="{{ route('inventario.productos.listado') }}"
                        @if (request()->routeIs('inventario.listado', 'inventario.productos.*')) aria-current="page" @endif
                    >Productos</a>
                    <a
                        href="{{ route('inventario.servicios.listado') }}"
                        @if (request()->routeIs('inventario.servicios.*')) aria-current="page" @endif
                    >Servicios</a>
                    <a
                        href="{{ route('inventario.categorias.listado') }}"
                        @if (request()->routeIs('inventario.categorias.*')) aria-current="page" @endif
                    >Categorías</a>
                    <a href="{{ route('inventario.listado') }}#series-vendidas">Series vendidas</a>
                </div>
            </details>
        @endif

        <details
            data-grupo="reportes"
            data-activo="{{ $reportesActivos ? 'true' : 'false' }}"
            @if ($reportesActivos) open @endif
        >
            <summary>
                <span><x-icono nombre="reportes" /> Reportes</span>
                <x-icono nombre="flecha" />
            </summary>
            <div class="menu-administrativo__subgrupo">
                <a
                    href="{{ route('reportes.resumen') }}"
                    @if (request()->routeIs('reportes.resumen')) aria-current="page" @endif
                >Resumen</a>
                <a
                    href="{{ route('reportes.cotizaciones') }}"
                    @if (request()->routeIs('reportes.cotizaciones')) aria-current="page" @endif
                >Cotizaciones</a>
                <a
                    data-enlace="reporte-ventas"
                    href="{{ route('reportes.ventas') }}"
                    @if (request()->routeIs('reportes.ventas')) aria-current="page" @endif
                >Ventas</a>
            </div>
        </details>

        @if ($usuarioNavegacion->esAdministrador())
            <details data-grupo="administracion" @if ($administracionActiva) open @endif>
                <summary>
                    <span><x-icono nombre="administracion" /> Administración</span>
                    <x-icono nombre="flecha" />
                </summary>
                <div class="menu-administrativo__subgrupo">
                    <a
                        href="{{ route('administracion.usuarios.listado') }}"
                        @if (request()->routeIs('administracion.usuarios.*')) aria-current="page" @endif
                    >Usuarios</a>
                </div>
            </details>
        @endif

        <a
            class="menu-administrativo__enlace"
            href="{{ route('perfil.mostrar') }}"
            @if (request()->routeIs('perfil.*')) aria-current="page" @endif
        >
            <x-icono nombre="perfil" />
            <span>Mi perfil</span>
        </a>
    </nav>
</aside>
