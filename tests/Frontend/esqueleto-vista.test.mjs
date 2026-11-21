import assert from 'node:assert/strict';
import { existsSync, readFileSync } from 'node:fs';
import { test } from 'node:test';

const ruta = new URL('../../resources/views/components/esqueleto-carga.blade.php', import.meta.url);

test('el componente de skeleton define las tres variantes y el estado decorativo', () => {
    assert.equal(existsSync(ruta), true);

    const fuente = readFileSync(ruta, 'utf8');
    assert.match(fuente, /id="esqueleto-carga"/);
    assert.match(fuente, /aria-hidden="true"/);
    assert.match(fuente, /data-esqueleto-variante="panel"/);
    assert.match(fuente, /data-esqueleto-variante="listado"/);
    assert.match(fuente, /data-esqueleto-variante="formulario"/);
});
