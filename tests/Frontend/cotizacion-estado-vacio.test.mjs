import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

const estilos = readFileSync('public/css/administracion.css', 'utf8');

test('el estado vacío se oculta cuando la cotización ya contiene filas', () => {
    assert.match(
        estilos,
        /\.creador-cotizacion__vacio\[hidden\]\s*\{[^}]*display:\s*none/,
    );
});
