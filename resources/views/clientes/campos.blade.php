<x-campo-formulario id="tipo-cliente" nombre="tipo" etiqueta="Tipo de persona" tipo="select" required>
    <option value="moral" @selected(old('tipo', $cliente?->tipo ?? 'moral') === 'moral')>Persona moral</option>
    <option value="fisica" @selected(old('tipo', $cliente?->tipo) === 'fisica')>Persona física</option>
</x-campo-formulario>
<x-campo-formulario id="nombre-cliente" nombre="nombre" etiqueta="Nombre o razón social" :valor="old('nombre', $cliente?->nombre)" maxlength="255" required />
<x-campo-formulario id="rfc-cliente" nombre="rfc" etiqueta="RFC" :valor="old('rfc', $cliente?->rfc)" maxlength="13" required />
<x-campo-formulario id="correo-cliente" nombre="correo" etiqueta="Correo electrónico" tipo="email" :valor="old('correo', $cliente?->correo)" required />
<x-campo-formulario id="telefono-cliente" nombre="telefono" etiqueta="Teléfono" :valor="old('telefono', $cliente?->telefono)" inputmode="tel" maxlength="30" required />
<x-campo-formulario id="codigo-postal-cliente" nombre="codigo_postal" etiqueta="Código postal" :valor="old('codigo_postal', $cliente?->codigo_postal)" inputmode="numeric" maxlength="10" required />
<x-campo-formulario id="direccion-cliente" nombre="direccion" etiqueta="Dirección" contenedor="campo-formulario campo-formulario--ancho" :valor="old('direccion', $cliente?->direccion)" maxlength="255" required />
