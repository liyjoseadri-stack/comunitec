import {readFileSync} from 'node:fs';
import {test} from 'node:test';
import assert from 'node:assert/strict';
const vista = readFileSync(new URL('../../resources/views/cotizaciones/crear.blade.php', import.meta.url), 'utf8');
const fuente = readFileSync(new URL('../../public/js/creador-cotizacion.js', import.meta.url), 'utf8');
const estilos = readFileSync(new URL('../../public/css/administracion.css', import.meta.url), 'utf8');

test('la cotización usa acciones y diálogos en lugar del selector inline de plantillas', () => {
    assert.doesNotMatch(vista, /data-plantilla-pc/);
    assert.match(vista, /data-abrir-plantillas-pc/);
    assert.match(vista, /data-abrir-personalizar-pc/);
    assert.match(vista, /data-dialogo-pc-armada/);
    assert.match(vista, /data-dialogo-plantillas-pc/);
    assert.match(vista, /data-dialogo-editor-pc/);
    assert.match(vista, /data-pcs-resumen/);
});

test('el creador conecta el modal con la selección de plantillas y la edición de resúmenes', () => {
    assert.match(fuente, /data-abrir-plantillas-pc/);
    assert.match(fuente, /data-abrir-personalizar-pc/);
    assert.match(fuente, /data-dialogo-plantillas-pc/);
    assert.match(fuente, /data-dialogo-editor-pc/);
    assert.match(fuente, /data-pcs-resumen/);
    assert.match(fuente, /Editar PC/);
});

test('la vista declara diálogos accesibles y un estado vacío para plantillas', () => {
    assert.match(vista, /<dialog[^>]+data-dialogo-pc-armada/);
    assert.match(vista, /aria-labelledby=/);
    assert.match(vista, /data-plantillas-pc-vacio/);
    assert.match(vista, /data-cerrar-dialogo-pc/);
});

test('la cotización inicia sin partidas vacías y ofrece un estado claro para agregarlas', () => {
    assert.match(vista, /data-conceptos-vacio/);
    assert.match(vista, /Agregar producto o servicio/);
    assert.match(vista, /data-tabla-conceptos/);
    assert.match(fuente, /data-conceptos-vacio/);
    assert.doesNotMatch(fuente, /configurarCliente\(\);\s*crearFila\(\);/);
});

test('la captura separa productos, PCs armadas y el cierre de la cotización', () => {
    assert.match(vista, /Datos de la cotización/);
    assert.match(vista, /Productos y servicios/);
    assert.match(vista, /PC armadas/);
    assert.match(vista, /Resumen de la cotización/);
    assert.match(estilos, /\.creador-cotizacion__seccion/);
});

test('los estilos del modal y los resúmenes tienen comportamiento responsive y reducido', () => {
    assert.match(estilos, /\.dialogo-pc-armada/);
    assert.match(estilos, /\.pc-plantilla-opcion/);
    assert.match(estilos, /\.cotizacion-pc-resumen/);
    assert.match(estilos, /@media \(prefers-reduced-motion: reduce\)/);
    assert.match(estilos, /dialogo-pc-armada__superficie[^{}]*\{[^}]*max-width/);
});
