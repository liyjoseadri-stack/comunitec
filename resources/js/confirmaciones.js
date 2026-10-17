
const iniciarConfirmaciones = () => {
    const dialogo = document.getElementById('dialogo-confirmacion');
    const titulo = document.getElementById('confirmacion-titulo');
    const mensaje = document.getElementById('confirmacion-mensaje');
    const volver = dialogo?.querySelector('[data-confirmacion-volver]');
    const aceptar = dialogo?.querySelector('[data-confirmacion-aceptar]');
    const autorizados = new WeakSet();
    let enviados = new WeakSet();
    let pendiente = null;

    const restaurarFoco = (origen) => {
        if (origen?.isConnected) origen.focus({ preventScroll: true });
    };

    const cerrar = () => {
        const actual = pendiente;
        pendiente = null;
        if (dialogo?.open) dialogo.close();
        if (actual) restaurarFoco(actual.origen);
    };

    const enviarConfirmado = (actual) => {
        restaurarFoco(actual.origen);
        autorizados.add(actual.formulario);
        try {
            // requestSubmit conserva validación, método, CSRF y el botón que inició el envío.
            if (actual.boton && !actual.boton.disabled && actual.boton.form === actual.formulario) {
                actual.formulario.requestSubmit(actual.boton);
            } else {
                actual.formulario.requestSubmit();
            }
        } finally {
            // Si la validación HTML impide submit, el próximo intento vuelve a confirmar.
            autorizados.delete(actual.formulario);
        }
    };

    volver?.addEventListener('click', cerrar);
    dialogo?.addEventListener('cancel', (evento) => {
        evento.preventDefault();
        cerrar();
    });
    dialogo?.addEventListener('close', () => {
        if (!dialogo.open) cerrar();
    });
    aceptar?.addEventListener('click', () => {
        if (!pendiente) return;
        const actual = pendiente;
        cerrar();
        enviarConfirmado(actual);
    });

    document.addEventListener('submit', (evento) => {
        const formulario = evento.target;
        if (!formulario.matches('form[data-confirmar]') || evento.defaultPrevented) return;
        if (enviados.has(formulario)) {
            evento.preventDefault();
            return;
        }
        if (autorizados.delete(formulario)) {
            enviados.add(formulario);
            return;
        }
        if (pendiente) {
            evento.preventDefault();
            return;
        }

        const textoTitulo = formulario.dataset.confirmarTitulo || 'Confirmar acción';
        const textoMensaje = formulario.dataset.confirmarMensaje || '¿Quieres continuar con esta acción?';
        const confirmarNativo = () => window.confirm(`${textoTitulo}\n\n${textoMensaje}`);

        // La confirmación del navegador mantiene operable el envío en implementaciones antiguas.
        if (!dialogo || typeof dialogo.showModal !== 'function' || typeof dialogo.close !== 'function'
            || typeof formulario.requestSubmit !== 'function' || !volver || !aceptar || !titulo || !mensaje) {
            if (confirmarNativo()) enviados.add(formulario);
            else evento.preventDefault();
            return;
        }

        titulo.textContent = textoTitulo;
        mensaje.textContent = textoMensaje;
        aceptar.textContent = formulario.dataset.confirmarEtiqueta || 'Confirmar';
        pendiente = { formulario, boton: evento.submitter, origen: evento.submitter || document.activeElement };
        try {
            dialogo.showModal();
            volver.focus({ preventScroll: true });
            evento.preventDefault();
        } catch {
            cerrar();
            // Conservar el envío original evita reentrar en submit si falla la apertura.
            if (confirmarNativo()) enviados.add(formulario);
            else evento.preventDefault();
        }
    });

    // Volver desde la caché de navegación permite una nueva acción deliberada.
    window.addEventListener('pageshow', () => { enviados = new WeakSet(); });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', iniciarConfirmaciones, { once: true });
} else {
    iniciarConfirmaciones();
}
