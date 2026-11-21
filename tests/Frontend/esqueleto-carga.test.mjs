import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { test } from 'node:test';
import postcss from 'postcss';

const css = postcss.parse(readFileSync(new URL('../../public/css/administracion.css', import.meta.url), 'utf8'));

function declaraciones(selector) {
    const resultado = {};

    css.walkRules((regla) => {
        if (!regla.selectors.includes(selector)) return;
        regla.walkDecls((declaracion) => { resultado[declaracion.prop] = declaracion.value; });
    });

    return resultado;
}

function contieneRegla(selector, propiedad, valor) {
    let encontrada = false;
    css.walkRules((regla) => {
        if (!regla.selectors.includes(selector)) return;
        regla.walkDecls((declaracion) => {
            if (declaracion.prop === propiedad && declaracion.value === valor) encontrada = true;
        });
    });
    return encontrada;
}

test('el skeleton ocupa el contenido principal sin crear desplazamiento horizontal', () => {
    const skeleton = declaraciones('.esqueleto-carga');
    assert.equal(skeleton.position, 'absolute');
    assert.equal(skeleton.inset, '0');
    assert.equal(skeleton['max-width'], '100%');
    assert.equal(skeleton['overflow-x'], 'hidden');
});

test('el skeleton define un estado reducido para usuarios que prefieren menos movimiento', () => {
    assert.equal(contieneRegla('.esqueleto-carga__bloque', 'animation-name', 'esqueleto-pulso'), true);
    assert.equal(contieneRegla('.esqueleto-carga__bloque', 'animation-timing-function', 'ease-out'), true);

    let reducido = false;
    css.walkAtRules('media', (regla) => {
        if (!regla.params.includes('prefers-reduced-motion: reduce')) return;
        regla.walkRules((hijo) => {
            if (!hijo.selectors.includes('.esqueleto-carga__bloque')) return;
            hijo.walkDecls((declaracion) => {
                if (declaracion.prop === 'animation' && declaracion.value === 'none') reducido = true;
            });
        });
    });

    assert.equal(reducido, true);
});
