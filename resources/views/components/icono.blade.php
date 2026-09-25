@props(['nombre'])

<svg
    {{ $attributes->class('icono-interfaz') }}
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="1.8"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
>
    @switch($nombre)
        @case('inicio')
            <path d="M3 11.5 12 4l9 7.5" />
            <path d="M5.5 10.5V20h13v-9.5M9.5 20v-6h5v6" />
            @break
        @case('cotizaciones')
            <path d="M6 3h9l3 3v15H6z" />
            <path d="M14 3v4h4M9 11h6M9 15h6" />
            @break
        @case('ventas')
            <path d="M4 7h16v13H4zM7 4h10v3" />
            <path d="M8 12h8M8 16h5" />
            @break
        @case('clientes')
            <circle cx="9" cy="8" r="3" />
            <path d="M3.5 20c.4-4 2.2-6 5.5-6s5.1 2 5.5 6M16 8.5a2.5 2.5 0 0 1 0 5M17 15c2.1.5 3.2 2.1 3.5 5" />
            @break
        @case('inventario')
            <path d="m4 7 8-4 8 4-8 4z" />
            <path d="M4 7v10l8 4 8-4V7M12 11v10" />
            @break
        @case('reportes')
            <path d="M4 20V10M10 20V4M16 20v-7M22 20H2" />
            @break
        @case('administracion')
            <circle cx="12" cy="12" r="3" />
            <path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-2.8 2.8-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.6v.2h-4V21a1.7 1.7 0 0 0-1-1.6 1.7 1.7 0 0 0-1.9.3l-.1.1L4.2 17l.1-.1a1.7 1.7 0 0 0 .3-1.9A1.7 1.7 0 0 0 3 14H2.8v-4H3a1.7 1.7 0 0 0 1.6-1 1.7 1.7 0 0 0-.3-1.9L4.2 7 7 4.2l.1.1A1.7 1.7 0 0 0 9 4.6 1.7 1.7 0 0 0 10 3v-.2h4V3a1.7 1.7 0 0 0 1 1.6 1.7 1.7 0 0 0 1.9-.3l.1-.1L19.8 7l-.1.1a1.7 1.7 0 0 0-.3 1.9 1.7 1.7 0 0 0 1.6 1h.2v4H21a1.7 1.7 0 0 0-1.6 1Z" />
            @break
        @case('perfil')
            <circle cx="12" cy="8" r="4" />
            <path d="M4.5 21c.5-5 3-7.5 7.5-7.5s7 2.5 7.5 7.5" />
            @break
        @case('menu')
            <path d="M4 7h16M4 12h16M4 17h16" />
            @break
        @case('cerrar')
            <path d="M10 5H5v14h5M14 8l4 4-4 4M18 12H9" />
            @break
        @case('flecha')
            <path d="m8 10 4 4 4-4" />
            @break
        @default
            <circle cx="12" cy="12" r="8" />
    @endswitch
</svg>
