import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { test } from 'node:test';
import { runInNewContext } from 'node:vm';

const fuente = readFileSync(new URL('../../resources/js/confirmaciones.js', import.meta.url), 'utf8');

function entorno({ nativo = true, cargando = false, fallaModal = false, respuesta = true, dialogoPresente = true } = {}) {
    const eventos = new Map();
    const nodos = new Map();
    let consultas = 0;
    let confirmaciones = 0;
    const documento = {
        readyState: cargando ? 'loading' : 'complete',
        activeElement: null,
        getElementById: (id) => { consultas++; return nodos.get(id) ?? null; },
        addEventListener: (nombre, fn) => eventos.set(nombre, fn),
    };
    const ventana = {
        confirm: () => { confirmaciones++; return respuesta; },
        addEventListener: (nombre, fn) => eventos.set(nombre, fn),
    };
    function elemento() {
        const listeners = new Map();
        return {
            textContent: '', disabled: false, isConnected: true, focos: 0,
            addEventListener: (nombre, fn) => listeners.set(nombre, fn),
            emitir(nombre) {
                const evento = { defaultPrevented: false, preventDefault() { this.defaultPrevented = true; } };
                listeners.get(nombre)?.(evento);
                return evento;
            },
            focus() { this.focos++; documento.activeElement = this; },
        };
    }
    const volver = elemento();
    const aceptar = elemento();
    const titulo = elemento();
    const mensaje = elemento();
    const dialogo = {
        ...elemento(), open: false,
        showModal: nativo ? function () { if (fallaModal) throw new Error('Modal no disponible'); this.open = true; } : undefined,
        close() { this.open = false; this.emitir('close'); },
        querySelector: (selector) => ({ '[data-confirmacion-volver]': volver, '[data-confirmacion-aceptar]': aceptar })[selector],
    };
    if (dialogoPresente) nodos.set('dialogo-confirmacion', dialogo);
    nodos.set('confirmacion-titulo', titulo);
    nodos.set('confirmacion-mensaje', mensaje);

    function formulario({ confirmar = true, valido = true, requestSubmit = true } = {}) {
        const boton = elemento();
        const form = {
            dataset: { confirmarTitulo: 'Desactivar producto', confirmarMensaje: 'Equipo <seguro>: no estará disponible.', confirmarEtiqueta: 'Desactivar' },
            enviados: 0, submitters: [], disparandoSubmit: false, intentosRequestSubmit: 0,
            matches: (selector) => confirmar && selector === 'form[data-confirmar]',
            requestSubmit: requestSubmit ? function (submitter) {
                this.intentosRequestSubmit++;
                // HTML no vuelve a enviar mientras se despacha el submit del mismo formulario.
                if (valido && !this.disparandoSubmit) enviar(form, submitter);
            } : undefined,
        };
        boton.form = form;
        return { form, boton };
    }
    function enviar(form, submitter) {
        const evento = { target: form, submitter, defaultPrevented: false, preventDefault() { this.defaultPrevented = true; } };
        form.disparandoSubmit = true;
        try {
            eventos.get('submit')?.(evento);
        } finally {
            form.disparandoSubmit = false;
        }
        if (!evento.defaultPrevented) { form.enviados++; form.submitters.push(submitter); }
        return evento;
    }
    runInNewContext(fuente, { document: documento, window: ventana, WeakSet });
    return { eventos, documento, dialogo, volver, aceptar, titulo, mensaje, formulario, enviar, consultas: () => consultas, confirmaciones: () => confirmaciones };
}

test('espera al DOM y no intercepta acciones sin data-confirmar', () => {
    const e = entorno({ cargando: true });
    assert.equal(e.consultas(), 0);
    e.eventos.get('DOMContentLoaded')();
    const { form, boton } = e.formulario({ confirmar: false });
    e.enviar(form, boton);
    assert.equal(form.enviados, 1);
    assert.equal(e.dialogo.open, false);
});

test('abre modal, usa texto seguro, enfoca Volver y bloquea doble apertura', () => {
    const e = entorno();
    const { form, boton } = e.formulario();
    e.enviar(form, boton);
    e.enviar(form, boton);
    assert.equal(form.enviados, 0);
    assert.equal(e.dialogo.open, true);
    assert.equal(e.titulo.textContent, form.dataset.confirmarTitulo);
    assert.equal(e.mensaje.textContent, form.dataset.confirmarMensaje);
    assert.equal(e.aceptar.textContent, 'Desactivar');
    assert.equal(e.volver.focos, 1);
});

for (const accion of ['volver', 'escape', 'cierre']) {
    test(`${accion} cancela sin enviar y devuelve foco al botón invocador`, () => {
        const e = entorno();
        const { form, boton } = e.formulario();
        e.enviar(form, boton);
        if (accion === 'volver') e.volver.emitir('click');
        if (accion === 'escape') assert.equal(e.dialogo.emitir('cancel').defaultPrevented, true);
        if (accion === 'cierre') e.dialogo.close();
        assert.equal(form.enviados, 0);
        assert.equal(e.dialogo.open, false);
        assert.equal(e.documento.activeElement, boton);
        e.enviar(form, boton);
        assert.equal(e.dialogo.open, true);
    });
}

test('confirmar preserva submitter y envía una sola vez incluso con clic/submit repetido', () => {
    const e = entorno();
    const { form, boton } = e.formulario();
    e.enviar(form, boton);
    e.aceptar.emitir('click');
    e.aceptar.emitir('click');
    e.enviar(form, boton);
    assert.equal(form.enviados, 1);
    assert.equal(form.submitters[0], boton);
    assert.equal(e.dialogo.open, false);
    assert.equal(e.documento.activeElement, boton);
    e.eventos.get('pageshow')();
    e.enviar(form, boton);
    assert.equal(e.dialogo.open, true);
});

test('un formulario inválido no conserva autorización para un intento posterior', () => {
    const e = entorno();
    const { form, boton } = e.formulario({ valido: false });
    e.enviar(form, boton);
    e.aceptar.emitir('click');
    assert.equal(form.enviados, 0);
    e.enviar(form, boton);
    assert.equal(e.dialogo.open, true);
});

test('conserva el formulario pendiente ante otro submit y recupera foco al enviar con Enter', () => {
    const e = entorno();
    const primero = e.formulario();
    const segundo = e.formulario();
    primero.boton.focus();
    e.enviar(primero.form, null);
    e.enviar(segundo.form, segundo.boton);
    e.aceptar.emitir('click');
    assert.equal(primero.form.enviados, 1);
    assert.equal(segundo.form.enviados, 0);
    assert.equal(e.documento.activeElement, primero.boton);
});

for (const opciones of [{ nativo: false }, { fallaModal: true }, { dialogoPresente: false }]) {
    for (const respuesta of [true, false]) {
        test(`fallback confirma o cancela sin bloquear el formulario: ${JSON.stringify({ ...opciones, respuesta })}`, () => {
            const e = entorno({ ...opciones, respuesta });
            const { form, boton } = e.formulario();
            const envioOriginal = e.enviar(form, boton);
            assert.equal(form.enviados, respuesta ? 1 : 0);
            assert.equal(e.confirmaciones(), 1);
            assert.equal(e.dialogo.open, false);
            assert.equal(envioOriginal.defaultPrevented, !respuesta);
            assert.equal(form.intentosRequestSubmit, 0);
            if (respuesta) {
                assert.equal(form.submitters[0], boton);
                e.enviar(form, boton);
                assert.equal(form.enviados, 1);
            }
        });
    }
}

test('sin requestSubmit usa confirmación síncrona y conserva el envío original', () => {
    const e = entorno();
    const { form, boton } = e.formulario({ requestSubmit: false });
    e.enviar(form, boton);
    assert.equal(form.enviados, 1);
    assert.equal(form.submitters[0], boton);
    assert.equal(e.confirmaciones(), 1);
});
