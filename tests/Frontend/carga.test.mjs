import assert from 'node:assert/strict';
import { existsSync, readFileSync } from 'node:fs';
import { test } from 'node:test';
import { runInNewContext } from 'node:vm';

const rutaFuente = new URL('../../resources/js/carga.js', import.meta.url);
const fuente = existsSync(rutaFuente) ? readFileSync(rutaFuente, 'utf8') : '';

function entorno() {
    const eventos = new Map();
    const nodos = new Map();
    const temporizadores = [];
    const atributos = new Map();
    const carga = {
        hidden: true,
        dataset: {},
        setAttribute(nombre, valor) { atributos.set(nombre, valor); },
        removeAttribute(nombre) { atributos.delete(nombre); },
        getAttribute(nombre) { return atributos.get(nombre) ?? null; },
    };
    const contenido = {
        dataset: { contextoCarga: 'listado' },
        setAttribute(nombre, valor) { atributos.set(`main:${nombre}`, valor); },
        removeAttribute(nombre) { atributos.delete(`main:${nombre}`); },
        getAttribute(nombre) { return atributos.get(`main:${nombre}`) ?? null; },
    };
    const documento = {
        readyState: 'complete',
        documentElement: { dataset: {} },
        location: { href: 'http://localhost/panel', origin: 'http://localhost' },
        getElementById(id) { return nodos.get(id) ?? null; },
        addEventListener(nombre, funcion) { eventos.set(nombre, funcion); },
    };
    const ventana = {
        addEventListener(nombre, funcion) { eventos.set(nombre, funcion); },
    };

    nodos.set('esqueleto-carga', carga);
    nodos.set('contenido-principal', contenido);

    const ejecutarTemporizadores = () => {
        while (temporizadores.length) temporizadores.shift()();
    };

    const emitir = (nombre, evento) => eventos.get(nombre)?.(evento);
    const programar = (funcion) => { temporizadores.push(funcion); return temporizadores.length; };
    const cancelar = (identificador) => { temporizadores[identificador - 1] = () => {}; };

    runInNewContext(fuente, {
        document: documento,
        window: ventana,
        URL,
        setTimeout: programar,
        clearTimeout: cancelar,
    });

    return { carga, contenido, documento, emitir, ejecutarTemporizadores };
}

function enlace({ href = '/clientes', target = '', descarga = false, boton = 0, modificadores = false } = {}) {
    return {
        href: new URL(href, 'http://localhost/panel').href,
        target,
        dataset: {},
        hasAttribute(nombre) { return nombre === 'download' && descarga; },
        closest() { return this; },
        boton,
        modificadores,
    };
}

function eventoClic(origen, opciones = {}) {
    return {
        target: origen,
        button: opciones.boton ?? 0,
        metaKey: opciones.metaKey ?? false,
        ctrlKey: opciones.ctrlKey ?? false,
        shiftKey: opciones.shiftKey ?? false,
        altKey: opciones.altKey ?? false,
        defaultPrevented: false,
    };
}

function formulario({ metodo = 'POST', confirmar = false, ajax = false, variante } = {}) {
    return {
        method: metodo,
        dataset: {
            ...(confirmar ? { confirmar: 'true' } : {}),
            ...(ajax ? { formularioCotizacion: 'true' } : {}),
            ...(variante ? { cargaVariante: variante } : {}),
        },
        matches(selector) {
            if (selector === 'form[data-confirmar]') return confirmar;
            if (selector === 'form[data-formulario-cotizacion]') return ajax;
            if (selector === 'form[data-carga="false"]') return false;
            return selector === 'form.formulario-administrativo';
        },
    };
}

function eventoSubmit(form, { preventDefault = false } = {}) {
    return {
        target: form,
        defaultPrevented: preventDefault,
        preventDefault() { this.defaultPrevented = true; },
    };
}

test('muestra la variante de listado después de una navegación interna elegible', () => {
    const e = entorno();
    e.emitir('click', eventoClic(enlace()));
    assert.equal(e.carga.hidden, true);
    e.ejecutarTemporizadores();
    assert.equal(e.carga.hidden, false);
    assert.equal(e.carga.dataset.variante, 'listado');
    assert.equal(e.contenido.getAttribute('aria-busy'), 'true');
    assert.equal(e.documento.documentElement.dataset.cargando, 'true');
});

test('excluye enlaces externos, descargas, anclas, pestañas nuevas y modificadores', () => {
    for (const opciones of [
        { href: 'https://ejemplo.com/clientes' },
        { href: '/panel#alertas' },
        { href: '/reporte.pdf', descarga: true },
        { href: '/clientes', target: '_blank' },
    ]) {
        const e = entorno();
        e.emitir('click', eventoClic(enlace(opciones)));
        e.ejecutarTemporizadores();
        assert.equal(e.carga.hidden, true);
    }

    const e = entorno();
    e.emitir('click', eventoClic(enlace(), { metaKey: true }));
    e.ejecutarTemporizadores();
    assert.equal(e.carga.hidden, true);
});

test('muestra formulario y conserva la variante declarada', () => {
    const e = entorno();
    e.emitir('submit', eventoSubmit(formulario({ variante: 'formulario' })));
    e.ejecutarTemporizadores();
    assert.equal(e.carga.dataset.variante, 'formulario');
    assert.equal(e.contenido.getAttribute('aria-busy'), 'true');
});

test('no muestra carga para un submit cancelado o para el formulario AJAX de cotización', () => {
    const confirmado = entorno();
    confirmado.emitir('submit', eventoSubmit(formulario({ confirmar: true }), { preventDefault: true }));
    confirmado.ejecutarTemporizadores();
    assert.equal(confirmado.carga.hidden, true);

    const ajax = entorno();
    ajax.emitir('submit', eventoSubmit(formulario({ ajax: true })));
    ajax.ejecutarTemporizadores();
    assert.equal(ajax.carga.hidden, true);
});

test('pageshow limpia el skeleton y aria-busy para permitir otra acción', () => {
    const e = entorno();
    e.emitir('click', eventoClic(enlace()));
    e.ejecutarTemporizadores();
    e.emitir('pageshow');
    assert.equal(e.carga.hidden, true);
    assert.equal(e.contenido.getAttribute('aria-busy'), null);
    assert.equal(e.documento.documentElement.dataset.cargando, undefined);
});
