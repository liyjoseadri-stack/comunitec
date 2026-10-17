<dialog class="dialogo-confirmacion" id="dialogo-confirmacion" aria-labelledby="confirmacion-titulo" aria-describedby="confirmacion-mensaje">
    <h2 id="confirmacion-titulo">Confirmar acción</h2>
    <p id="confirmacion-mensaje"></p>
    <div class="dialogo-confirmacion__acciones">
        <x-boton variante="secundario" data-confirmacion-volver autofocus>Volver</x-boton>
        <x-boton variante="peligro" data-confirmacion-aceptar>Confirmar</x-boton>
    </div>
</dialog>
