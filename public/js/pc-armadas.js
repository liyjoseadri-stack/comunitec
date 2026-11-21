(function () {
    const formulario = document.querySelector('[data-formulario-pc-armada]');

    if (!formulario) {
        return;
    }

    let indice = 1000;

    const filtrarProductos = (fila) => {
        const seccion = fila.closest('[data-seccion-pc]');
        const tipos = seccion.dataset.tiposPc.split(',');
        const selector = fila.querySelector('[data-producto-componente]');
        const tipoOculto = fila.querySelector('[name$="[tipo_componente_pc]"]');
        const productoInicial = selector.dataset.productoInicial || selector.value;

        Array.from(selector.options).forEach((opcion) => {
            if (!opcion.dataset.tipo) {
                return;
            }
            opcion.hidden = !tipos.includes(opcion.dataset.tipo);
            opcion.disabled = opcion.hidden;
        });

        if (productoInicial && !selector.querySelector(`option[value="${productoInicial}"]`)?.disabled) {
            selector.value = productoInicial;
        } else if (selector.value && selector.selectedOptions[0]?.disabled) {
            selector.value = '';
        }

        const productoSeleccionado = selector.selectedOptions[0];
        if (productoSeleccionado?.dataset.tipo) {
            tipoOculto.value = productoSeleccionado.dataset.tipo;
        }
    };

    const conectarFila = (fila) => {
        filtrarProductos(fila);
        fila.querySelector('[data-producto-componente]').addEventListener('change', () => filtrarProductos(fila));
        fila.querySelector('[data-eliminar-componente]').addEventListener('click', () => {
            const filas = fila.closest('[data-componentes]').querySelectorAll('[data-componente]');
            if (filas.length > 1) {
                fila.remove();
            } else {
                fila.querySelector('[data-producto-componente]').value = '';
            }
        });
    };

    const crearFila = (seccion) => {
        const filaBase = seccion.querySelector('[data-componente]');
        const fila = filaBase.cloneNode(true);
        const sufijo = indice++;
        fila.querySelectorAll('input, select').forEach((campo) => {
            campo.name = campo.name.replace(/componentes\[[^\]]+\]/, `componentes[${sufijo}]`);
            if (campo.id) {
                campo.id = `${campo.id}-${sufijo}`;
            }
            if (campo.tagName === 'SELECT') {
                campo.value = '';
                campo.dataset.productoInicial = '';
            }
        });
        fila.querySelectorAll('label').forEach((etiqueta) => {
            etiqueta.htmlFor = `${etiqueta.htmlFor}-${sufijo}`;
        });
        fila.querySelector('[data-cantidad-componente]').value = '1';
        seccion.querySelector('[data-componentes]').append(fila);
        conectarFila(fila);
        fila.querySelector('[data-producto-componente]').focus();
    };

    formulario.querySelectorAll('[data-componente]').forEach(conectarFila);
    formulario.querySelectorAll('[data-agregar-componente]').forEach((boton) => {
        boton.addEventListener('click', () => crearFila(boton.closest('[data-seccion-pc]')));
    });
}());
