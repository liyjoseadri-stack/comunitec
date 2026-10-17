<x-campo-formulario id="nombre-usuario" nombre="nombre" etiqueta="Nombre" :valor="old('nombre', $usuario?->nombre)" autocomplete="name" required />
<x-campo-formulario id="correo-usuario" nombre="correo" etiqueta="Correo electrónico" tipo="email" :valor="old('correo', $usuario?->correo)" autocomplete="email" required />
<x-campo-formulario id="rol-usuario" nombre="rol" etiqueta="Rol" tipo="select" required>
    <option value="comercial" @selected(old('rol', $usuario?->rol ?? 'comercial') === 'comercial')>Comercial</option>
    <option value="consulta" @selected(old('rol', $usuario?->rol) === 'consulta')>Consulta</option>
    <option value="administrador" @selected(old('rol', $usuario?->rol) === 'administrador')>Administrador</option>
</x-campo-formulario>
<x-campo-formulario
    id="contrasena-usuario"
    nombre="contrasena"
    etiqueta="{{ $usuario ? 'Nueva contraseña' : 'Contraseña' }}"
    tipo="password"
    autocomplete="new-password"
    :ayuda="$usuario ? 'Déjala vacía para conservar la contraseña actual.' : 'Usa al menos 8 caracteres.'"
    :required="! $usuario"
/>
<x-campo-formulario
    id="confirmar-contrasena-usuario"
    nombre="contrasena_confirmation"
    etiqueta="Confirmar contraseña"
    tipo="password"
    autocomplete="new-password"
    :required="! $usuario"
/>
