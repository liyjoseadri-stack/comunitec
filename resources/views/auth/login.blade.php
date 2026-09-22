<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión | Comunitec</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    <main class="mx-auto flex min-h-screen max-w-md items-center px-6">
        <section class="w-full rounded-xl bg-white p-8 shadow-sm">
            <h1 class="text-2xl font-semibold">Comunitec</h1>
            <p class="mt-2 text-sm text-slate-600">Inicia sesión para administrar cotizaciones y ventas.</p>

            <form class="mt-6 space-y-4" method="POST" action="{{ route('login.store') }}">
                @csrf
                <label class="block text-sm font-medium" for="email">Correo</label>
                <input class="w-full rounded border-slate-300" id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>
                @error('email')<p class="text-sm text-red-700">{{ $message }}</p>@enderror

                <label class="block text-sm font-medium" for="password">Contraseña</label>
                <input class="w-full rounded border-slate-300" id="password" name="password" type="password" required>

                <label class="flex items-center gap-2 text-sm" for="remember">
                    <input id="remember" name="remember" type="checkbox" value="1"> Recordarme
                </label>

                <button class="w-full rounded bg-slate-900 px-4 py-2 font-medium text-white" type="submit">Iniciar sesión</button>
            </form>
        </section>
    </main>
</body>
</html>
