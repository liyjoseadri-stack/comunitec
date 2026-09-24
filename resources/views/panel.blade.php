<!doctype html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>
            Panel | Comunitec
        </title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <link rel="stylesheet" href="{{ asset('css/navegacion.css') }}">
    </head>
    <body class="bg-slate-100 text-slate-900">
        @include('componentes.navegacion')
        <main class="mx-auto max-w-5xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">
                        Panel principal
                    </h1>
                    <p class="mt-1 text-slate-600">
                        Bienvenido, {{ auth()->user()->name }}.
                    </p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="rounded border border-slate-300 bg-white px-4 py-2" type="submit">
                        Cerrar sesión
                    </button>
                </form>
            </div>
            <section class="mt-8 rounded bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold">
                    Alertas de inventario
                </h2>
                @if ($lowStock->isEmpty())
                    <p class="mt-2 text-slate-600">
                        No hay productos con stock bajo.
                    </p>
                @else
                    <ul class="mt-2 list-disc pl-5 text-amber-800">
                        @foreach ($lowStock as $item)
                            <li>
                                {{ $item->name }}: {{ $item->available_units_count }} piezas disponibles.
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </main>
    </body>
</html>
