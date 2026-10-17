import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { test } from 'node:test';
import postcss from 'postcss';

const css = postcss.parse(readFileSync(new URL('../../public/css/administracion.css', import.meta.url), 'utf8'));

// Inspección estática de la cascada para estos selectores exactos. No calcula layout de navegador.
function declaraciones(selectores, ancho) {
    const resultado = {};
    css.walkRules((regla) => {
        if (!regla.selectors.some((selector) => selectores.includes(selector))) return;
        for (let padre = regla.parent; padre; padre = padre.parent) {
            if (padre.type !== 'atrule' || padre.name !== 'media') continue;
            for (const [, limite, valor, unidad] of padre.params.matchAll(/(max|min)-width:\s*([\d.]+)(rem|px)/g)) {
                const pixeles = Number(valor) * (unidad === 'rem' ? 16 : 1);
                if (limite === 'max' ? ancho > pixeles : ancho < pixeles) return;
            }
        }
        regla.walkDecls((declaracion) => { resultado[declaracion.prop] = declaracion.value; });
    });
    return resultado;
}

for (const ancho of [320, 375, 768, 1024, 1440]) {
    test(`Tablas a ${ancho}px: contención, tarjetas, instrucciones y acciones`, () => {
        const contenedor = declaraciones(['.contenedor-tabla', '.tabla-responsive'], ancho);
        assert.equal(contenedor['min-width'], '0');
        assert.equal(contenedor['max-width'], '100%');
        assert.equal(contenedor['overflow-x'], 'auto');

        const listado = declaraciones(['.tabla-registros', '.tabla-catalogo', '.tabla-adaptable'], ancho);
        const tecnica = declaraciones(['.tabla-registros'], ancho);
        const instruccion = declaraciones(['.tabla-responsive__instruccion'], ancho);
        assert.equal(tecnica['min-width'], '44rem');
        const vacia = declaraciones(['.tabla-registros', '.tabla-responsive--vacia .tabla-registros'], ancho);
        assert.equal(vacia['min-width'], '0');
        assert.equal(listado['min-width'], ancho <= 768 ? '0' : '54rem');
        const captura = declaraciones(['.tabla-registros', '.tabla-captura-cotizacion', '.tabla-registros.tabla-captura-cotizacion'], ancho);
        assert.equal(captura['min-width'], ancho <= 1200 ? '0' : '86rem');
        assert.equal(instruccion.display, ancho <= 768 ? 'block' : 'none');
        if (ancho <= 768) {
            const celda = declaraciones(['.tabla-adaptable tbody td'], ancho);
            assert.equal(celda.display, 'block');
            assert.equal(celda.width, '100%');
            const etiqueta = declaraciones(['.tabla-adaptable [data-etiqueta]::before'], ancho);
            assert.equal(etiqueta.content, 'attr(data-etiqueta)');
        }
        const accion = declaraciones(['.boton', '.boton--compacto', '.grupo-acciones-fila .boton'], ancho);
        assert.equal(accion['min-height'], '44px');
    });
}
