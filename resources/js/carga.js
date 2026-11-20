const DEMORA_CARGA = 120;
const VARIANTES_VALIDAS = new Set(['panel', 'listado', 'formulario']);

const esqueleto = document.getElementById('esqueleto-carga');
const contenidoPrincipal = document.getElementById('contenido-principal');
let temporizadorCarga = null;

const obtenerVariante = (origen) => {
    const variante = origen?.dataset?.cargaVariante
        || contenidoPrincipal?.dataset?.contextoCarga
        || 'listado';

    return VARIANTES_VALIDAS.has(variante) ? variante : 'listado';
};

const ocultarCarga = () => {
    if (temporizadorCarga !== null) {
        clearTimeout(temporizadorCarga);
        temporizadorCarga = null;
    }

    if (!esqueleto || !contenidoPrincipal) {
        return;
    }

    esqueleto.hidden = true;
    esqueleto.setAttribute('aria-hidden', 'true');
    contenidoPrincipal.removeAttribute('aria-busy');
    delete document.documentElement.dataset.cargando;
};

const mostrarCarga = (origen) => {
    temporizadorCarga = null;

    if (!esqueleto || !contenidoPrincipal) {
        return;
    }

    esqueleto.dataset.variante = obtenerVariante(origen);
    esqueleto.hidden = false;
    esqueleto.setAttribute('aria-hidden', 'false');
    contenidoPrincipal.setAttribute('aria-busy', 'true');
    document.documentElement.dataset.cargando = 'true';
};

const programarCarga = (origen) => {
    if (!esqueleto || !contenidoPrincipal) {
        return;
    }

    if (temporizadorCarga !== null) {
        clearTimeout(temporizadorCarga);
    }

    temporizadorCarga = setTimeout(() => mostrarCarga(origen), DEMORA_CARGA);
};

const esEnlaceElegible = (evento) => {
    const enlace = evento.target?.closest?.('a[href]');

    if (!enlace || evento.defaultPrevented) {
        return false;
    }

    if (evento.button !== undefined && evento.button !== 0) {
        return false;
    }

    if (evento.metaKey || evento.ctrlKey || evento.shiftKey || evento.altKey) {
        return false;
    }

    if (enlace.target && enlace.target !== '_self') {
        return false;
    }

    if (enlace.hasAttribute('download')) {
        return false;
    }

    const ubicacionActual = new URL(document.location.href);
    const destino = new URL(enlace.href, ubicacionActual.href);

    if (destino.origin !== ubicacionActual.origin) {
        return false;
    }

    if (destino.pathname === ubicacionActual.pathname
        && destino.search === ubicacionActual.search
        && destino.hash) {
        return false;
    }

    return !destino.pathname.toLowerCase().endsWith('.pdf');
};

const esFormularioElegible = (formulario, evento) => {
    if (evento.defaultPrevented
        || formulario.matches?.('form[data-formulario-cotizacion]')
        || formulario.matches?.('form[data-carga="false"]')) {
        return false;
    }

    return formulario.dataset.cargaVariante
        || formulario.method?.toUpperCase() === 'GET'
        || formulario.matches?.('form.formulario-administrativo');
};

document.addEventListener('click', (evento) => {
    if (esEnlaceElegible(evento)) {
        programarCarga(evento.target.closest('a[href]'));
    }
});

document.addEventListener('submit', (evento) => {
    if (esFormularioElegible(evento.target, evento)) {
        programarCarga(evento.target);
    }
});

window.addEventListener('pageshow', ocultarCarga);
