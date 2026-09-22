<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Usuarios | Comunitec</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 text-slate-900">
    <main class="mx-auto max-w-5xl p-6">
        <h1 class="text-2xl font-semibold">Administración de usuarios</h1>
        @if (session('status'))<p class="mt-2 text-sm text-green-700">{{ session('status') }}</p>@endif
        <form class="mt-6 grid gap-3 rounded bg-white p-5 shadow-sm" method="POST" action="{{ route('admin.users.store') }}">
            @csrf
            <input class="rounded border-slate-300" name="name" placeholder="Nombre" required>
            <input class="rounded border-slate-300" name="email" type="email" placeholder="Correo" required>
            <select class="rounded border-slate-300" name="role" required>
                <option value="comercial">Comercial</option><option value="consulta">Consulta</option><option value="admin">Administrador</option>
            </select>
            <input class="rounded border-slate-300" name="password" type="password" placeholder="Contraseña" required>
            <input class="rounded border-slate-300" name="password_confirmation" type="password" placeholder="Confirmar contraseña" required>
            <button class="rounded bg-slate-900 px-4 py-2 text-white" type="submit">Crear usuario</button>
        </form>
        <div class="mt-6 overflow-x-auto rounded bg-white shadow-sm">
            <table class="w-full text-left text-sm"><thead><tr><th class="p-3">Nombre</th><th>Correo</th><th>Rol</th><th>Estado</th><th></th></tr></thead>
                <tbody>@foreach ($users as $user)<tr class="border-t"><td class="p-3">{{ $user->name }}</td><td>{{ $user->email }}</td><td>{{ $user->role }}</td><td>{{ $user->active ? 'Activo' : 'Inactivo' }}</td><td><form method="POST" action="{{ route('admin.users.status', $user) }}">@csrf @method('PATCH')<input name="active" type="hidden" value="{{ $user->active ? 0 : 1 }}"><button class="underline" type="submit">{{ $user->active ? 'Desactivar' : 'Activar' }}</button></form><details class="mt-2"><summary class="cursor-pointer underline">Editar</summary><form class="mt-2 grid gap-2" method="POST" action="{{ route('admin.users.update', $user) }}">@csrf @method('PUT')<input class="rounded border-slate-300" name="name" value="{{ $user->name }}" required><input class="rounded border-slate-300" name="email" type="email" value="{{ $user->email }}" required><select class="rounded border-slate-300" name="role">@foreach (['admin' => 'Administrador', 'comercial' => 'Comercial', 'consulta' => 'Consulta'] as $value => $label)<option value="{{ $value }}" @selected($user->role === $value)>{{ $label }}</option>@endforeach</select><input class="rounded border-slate-300" name="password" type="password" placeholder="Nueva contraseña (opcional)"><input class="rounded border-slate-300" name="password_confirmation" type="password" placeholder="Confirmar nueva contraseña"><button class="rounded bg-slate-900 px-3 py-2 text-white" type="submit">Guardar cambios</button></form></details></td></tr>@endforeach</tbody>
            </table>
        </div>
    </main>
</body>
</html>
