import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { test } from 'node:test';

function fuente(ruta) {
    return readFileSync(new URL(`../../${ruta}`, import.meta.url), 'utf8');
}

test('la paginación declara la variante de listado', () => {
    assert.match(fuente('resources/views/componentes/paginacion.blade.php'), /data-carga-variante="listado"/);
});

test('los filtros de reportes declaran la variante de listado', () => {
    assert.match(fuente('resources/views/components/reportes/filtros.blade.php'), /data-carga-variante="listado"/);
});

test('el módulo de PC armadas usa listado en consulta y formulario en captura', () => {
    assert.match(fuente('resources/views/inventario/pc-armadas/listado.blade.php'), /@section\('contexto_carga', 'listado'\)/);

    for (const vista of ['crear', 'editar', 'detalle']) {
        assert.match(
            fuente(`resources/views/inventario/pc-armadas/${vista}.blade.php`),
            /@section\('contexto_carga', 'formulario'\)/,
        );
    }
});
