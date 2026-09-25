<!doctype html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>
            Usuarios | Comunitec
        </title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <link rel="stylesheet" href="{{ asset('css/navegacion.css') }}">
    </head>
    <body class="bg-slate-100 text-slate-900">
        @include('componentes.navegacion')
        <main class="mx-auto max-w-5xl p-6">
            <h1 class="text-2xl font-semibold">
                Administración de usuarios
            </h1>
            @if (session('estado'))
                <p class="mt-2 text-sm text-green-700">
                    {{ session('estado') }}
                </p>
            @endif
            <form class="mt-6 grid gap-3 rounded bg-white p-5 shadow-sm" method="POST" action="{{ route('administracion.usuarios.guardar') }}">
                @csrf
                <input class="rounded border-slate-300" name="nombre" placeholder="Nombre" required>
                <input class="rounded border-slate-300" name="correo" type="email" placeholder="Correo" required>
                <select class="rounded border-slate-300" name="rol" required>
                    <option value="comercial">
                        Comercial
                    </option>
                    <option value="consulta">
                        Consulta
                    </option>
                    <option value="administrador">
                        Administrador
                    </option>
                </select>
                <input class="rounded border-slate-300" name="contrasena" type="password" placeholder="Contraseña" required>
                <input class="rounded border-slate-300" name="contrasena_confirmation" type="password" placeholder="Confirmar contraseña" required>
                <button class="rounded bg-slate-900 px-4 py-2 text-white" type="submit">
                    Crear usuario
                </button>
            </form>
            <div class="mt-6 overflow-x-auto rounded bg-white shadow-sm">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr>
                            <th class="p-3">
                                Nombre
                            </th>
                            <th>
                                Correo
                            </th>
                            <th>
                                Rol
                            </th>
                            <th>
                                Estado
                            </th>
                            <th>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($usuarios as $usuario)
                            <tr class="border-t">
                                <td class="p-3">
                                    {{ $usuario->nombre }}
                                </td>
                                <td>
                                    {{ $usuario->correo }}
                                </td>
                                <td>
                                    {{ $usuario->rol }}
                                </td>
                                <td>
                                    {{ $usuario->activo ? 'Activo' : 'Inactivo' }}
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('administracion.usuarios.estado', $usuario) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input name="activo" type="hidden" value="{{ $usuario->activo ? 0 : 1 }}">
                                        <button class="underline" type="submit">
                                            {{ $usuario->activo ? 'Desactivar' : 'Activar' }}
                                        </button>
                                    </form>
                                    <details class="mt-2">
                                        <summary class="cursor-pointer underline">
                                            Editar
                                        </summary>
                                        <form class="mt-2 grid gap-2" method="POST" action="{{ route('administracion.usuarios.actualizar', $usuario) }}">
                                            @csrf
                                            @method('PUT')
                                            <input class="rounded border-slate-300" name="nombre" value="{{ $usuario->nombre }}" required>
                                            <input class="rounded border-slate-300" name="correo" type="email" value="{{ $usuario->correo }}" required>
                                            <select class="rounded border-slate-300" name="rol">
                                                @foreach (['administrador' => 'Administrador', 'comercial' => 'Comercial', 'consulta' => 'Consulta'] as $valor => $etiqueta)
                                                    <option value="{{ $valor }}" @selected($usuario->rol === $valor)>{{ $etiqueta }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <input class="rounded border-slate-300" name="contrasena" type="password" placeholder="Nueva contraseña (opcional)">
                                            <input class="rounded border-slate-300" name="contrasena_confirmation" type="password" placeholder="Confirmar nueva contraseña">
                                            <button class="rounded bg-slate-900 px-3 py-2 text-white" type="submit">
                                                Guardar cambios
                                            </button>
                                        </form>
                                    </details>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </main>
    </body>
</html>
