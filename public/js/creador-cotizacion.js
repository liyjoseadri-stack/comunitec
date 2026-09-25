(() => {
    const raiz = document.querySelector('[data-creador-cotizacion]');

    if (!raiz) {
        return;
    }

    const datos = JSON.parse(raiz.querySelector('[data-datos-cotizacion]').textContent);
    const formulario = raiz.querySelector('[data-formulario-cotizacion]');
    const cuerpo = raiz.querySelector('[data-filas-cotizacion]');
    const botonAgregar = raiz.querySelector('[data-agregar-concepto]');
    const botonGuardar = raiz.querySelector('[data-guardar-cotizacion]');
    const descuentoGeneral = raiz.querySelector('[name="porcentaje_descuento"]');
    const contenedorErrores = raiz.querySelector('.creador-cotizacion__errores');
    const filas = [];
    let consecutivo = 0;

    const moneda = new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN',
    });

    const normalizar = (valor) => String(valor ?? '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase();

    function configurarAutocompletado({entrada, lista, obtenerOpciones, alSeleccionar}) {
        let resultados = [];
        let activo = -1;

        const cerrar = () => {
            lista.hidden = true;
            lista.replaceChildren();
            entrada.setAttribute('aria-expanded', 'false');
            entrada.removeAttribute('aria-activedescendant');
            activo = -1;
        };

        const marcarActivo = (indice) => {
            activo = indice;
            const opciones = [...lista.querySelectorAll('[role="option"]')];
            opciones.forEach((opcion, posicion) => {
                opcion.setAttribute('aria-selected', posicion === activo ? 'true' : 'false');
            });
            const opcion = opciones[activo];
            if (opcion) {
                entrada.setAttribute('aria-activedescendant', opcion.id);
                opcion.scrollIntoView({block: 'nearest'});
            }
        };

        const seleccionar = (indice) => {
            const opcion = resultados[indice];
            if (!opcion) {
                return;
            }
            alSeleccionar(opcion);
            cerrar();
        };

        const mostrar = () => {
            const termino = normalizar(entrada.value.trim());
            if (termino.length < 2) {
                cerrar();
                return;
            }

            resultados = obtenerOpciones(termino).slice(0, 8);
            lista.replaceChildren();

            if (resultados.length === 0) {
                const vacio = document.createElement('p');
                vacio.className = 'lista-autocompletado__vacio';
                vacio.textContent = 'No se encontraron coincidencias.';
                lista.append(vacio);
            } else {
                resultados.forEach((opcion, indice) => {
                    const boton = document.createElement('button');
                    boton.type = 'button';
                    boton.id = `${lista.id}-opcion-${indice}`;
                    boton.setAttribute('role', 'option');
                    boton.setAttribute('aria-selected', 'false');

                    const principal = document.createElement('strong');
                    principal.textContent = opcion.nombre;
                    const detalle = document.createElement('span');
                    detalle.textContent = [opcion.codigo || opcion.rfc, opcion.categoria]
                        .filter(Boolean)
                        .join(' · ');
                    boton.append(principal, detalle);
                    boton.addEventListener('mousedown', (evento) => evento.preventDefault());
                    boton.addEventListener('click', () => seleccionar(indice));
                    lista.append(boton);
                });
            }

            lista.hidden = false;
            entrada.setAttribute('aria-expanded', 'true');
            activo = -1;
        };

        entrada.addEventListener('input', mostrar);
        entrada.addEventListener('focus', mostrar);
        entrada.addEventListener('blur', () => window.setTimeout(cerrar, 120));
        entrada.addEventListener('keydown', (evento) => {
            if (lista.hidden && evento.key === 'ArrowDown') {
                mostrar();
            }
            if (lista.hidden) {
                return;
            }
            if (evento.key === 'ArrowDown') {
                evento.preventDefault();
                marcarActivo(Math.min(activo + 1, resultados.length - 1));
            } else if (evento.key === 'ArrowUp') {
                evento.preventDefault();
                marcarActivo(Math.max(activo - 1, 0));
            } else if (evento.key === 'Enter' && activo >= 0) {
                evento.preventDefault();
                seleccionar(activo);
            } else if (evento.key === 'Escape') {
                cerrar();
            }
        });

        return {cerrar, mostrar};
    }

    function configurarCliente() {
        const entrada = raiz.querySelector('#busqueda-cliente');
        const lista = raiz.querySelector('#resultados-clientes');
        const id = raiz.querySelector('[data-cliente-id]');
        const confirmacion = raiz.querySelector('[data-cliente-seleccionado]');

        configurarAutocompletado({
            entrada,
            lista,
            obtenerOpciones: (termino) => datos.clientes.filter((cliente) =>
                normalizar(`${cliente.nombre} ${cliente.rfc}`).includes(termino)),
            alSeleccionar: (cliente) => {
                entrada.value = cliente.nombre;
                id.value = cliente.id;
                confirmacion.textContent = `${cliente.nombre} · RFC ${cliente.rfc || 'no registrado'}`;
            },
        });

        entrada.addEventListener('input', () => {
            id.value = '';
            confirmacion.textContent = 'Selecciona una coincidencia para confirmar el cliente.';
        });
    }

    function crearFila() {
        consecutivo += 1;
        const fila = document.createElement('tr');
        const idFila = consecutivo;
        fila.dataset.fila = String(idFila);
        fila.innerHTML = `
            <td data-label="Tipo">
                <select class="captura-tipo" aria-label="Tipo de concepto">
                    <option value="producto">Producto</option>
                    <option value="servicio">Servicio</option>
                </select>
            </td>
            <td data-label="Concepto / búsqueda">
                <div class="campo-autocompletado">
                    <input class="captura-concepto" type="search" autocomplete="off"
                        placeholder="Nombre, código o categoría" role="combobox"
                        aria-autocomplete="list" aria-expanded="false"
                        aria-controls="resultados-concepto-${idFila}">
                    <div id="resultados-concepto-${idFila}" class="lista-autocompletado" role="listbox" hidden></div>
                </div>
                <small class="captura-concepto__detalle">Escribe al menos 2 caracteres.</small>
            </td>
            <td data-label="Descripción adicional"><textarea class="captura-descripcion" rows="2" aria-label="Descripción adicional"></textarea></td>
            <td data-label="Cantidad"><input class="captura-cantidad" type="number" min="1" step="1" value="1" aria-label="Cantidad"></td>
            <td data-label="Precio unitario"><input class="captura-precio" type="number" min="0.01" step="0.01" value="0.00" aria-label="Precio unitario" readonly></td>
            <td data-label="Descuento (%)"><input class="captura-descuento" type="number" min="0" max="100" step="0.01" value="0" aria-label="Descuento porcentual"></td>
            <td data-label="Subtotal"><output class="captura-subtotal">$0.00</output></td>
            <td data-label="Acciones"><button class="boton boton--peligro boton--compacto captura-eliminar" type="button" aria-label="Eliminar concepto">Eliminar</button></td>
        `;

        const estado = {
            id: idFila,
            elemento: null,
            fila,
            tipo: fila.querySelector('.captura-tipo'),
            busqueda: fila.querySelector('.captura-concepto'),
            descripcion: fila.querySelector('.captura-descripcion'),
            cantidad: fila.querySelector('.captura-cantidad'),
            precio: fila.querySelector('.captura-precio'),
            descuento: fila.querySelector('.captura-descuento'),
            subtotal: fila.querySelector('.captura-subtotal'),
            detalle: fila.querySelector('.captura-concepto__detalle'),
        };
        filas.push(estado);
        cuerpo.append(fila);

        const lista = fila.querySelector('.lista-autocompletado');
        const autocompletado = configurarAutocompletado({
            entrada: estado.busqueda,
            lista,
            obtenerOpciones: (termino) => datos.catalogo.filter((elemento) =>
                elemento.tipo === estado.tipo.value
                && normalizar(`${elemento.nombre} ${elemento.codigo} ${elemento.categoria}`).includes(termino)),
            alSeleccionar: (elemento) => {
                estado.elemento = elemento;
                estado.busqueda.value = elemento.nombre;
                estado.descripcion.value = elemento.descripcion || elemento.nombre;
                estado.precio.value = Number(elemento.precio).toFixed(2);
                estado.detalle.textContent = `${elemento.codigo} · ${elemento.categoria || 'Sin categoría'}`;
                calcularTotales();
                estado.cantidad.focus();
                estado.cantidad.select();
            },
        });

        estado.tipo.addEventListener('change', () => {
            estado.elemento = null;
            estado.busqueda.value = '';
            estado.descripcion.value = '';
            estado.precio.value = '0.00';
            estado.detalle.textContent = 'Escribe al menos 2 caracteres.';
            autocompletado.cerrar();
            calcularTotales();
            estado.busqueda.focus();
        });
        estado.busqueda.addEventListener('input', () => {
            estado.elemento = null;
            estado.precio.value = '0.00';
            calcularTotales();
        });
        [estado.cantidad, estado.descuento].forEach((control) => {
            control.addEventListener('input', calcularTotales);
        });
        estado.descuento.addEventListener('keydown', (evento) => {
            if (evento.key === 'Enter') {
                evento.preventDefault();
                crearFila();
            }
        });
        fila.querySelector('.captura-eliminar').addEventListener('click', () => {
            const indice = filas.indexOf(estado);
            if (indice >= 0) {
                filas.splice(indice, 1);
            }
            fila.remove();
            if (filas.length === 0) {
                crearFila();
            }
            calcularTotales();
        });

        calcularTotales();
        window.requestAnimationFrame(() => estado.busqueda.focus());
    }

    function importeFila(estado) {
        const cantidad = Math.max(Number(estado.cantidad.value) || 0, 0);
        const precio = Math.max(Number(estado.precio.value) || 0, 0);
        const descuento = Math.min(Math.max(Number(estado.descuento.value) || 0, 0), 100);
        const bruto = cantidad * precio;

        return {
            bruto,
            descuento: bruto * descuento / 100,
            total: bruto * (1 - descuento / 100),
        };
    }

    function calcularTotales() {
        const importes = filas.map((fila) => {
            const importe = importeFila(fila);
            fila.subtotal.textContent = moneda.format(importe.total);
            return importe;
        });
        const bruto = importes.reduce((suma, importe) => suma + importe.bruto, 0);
        const descuentoLineas = importes.reduce((suma, importe) => suma + importe.descuento, 0);
        const totalLineas = bruto - descuentoLineas;
        const porcentajeGeneral = Math.min(Math.max(Number(descuentoGeneral.value) || 0, 0), 10);
        const descuento = descuentoLineas + totalLineas * porcentajeGeneral / 100;
        const total = bruto - descuento;
        const subtotalSinIva = total / 1.16;
        const iva = total - subtotalSinIva;

        raiz.querySelector('[data-total-bruto]').textContent = moneda.format(bruto);
        raiz.querySelector('[data-total-descuento]').textContent = `−${moneda.format(descuento)}`;
        raiz.querySelector('[data-total-subtotal]').textContent = moneda.format(subtotalSinIva);
        raiz.querySelector('[data-total-iva]').textContent = moneda.format(iva);
        raiz.querySelector('[data-total-final]').textContent = moneda.format(total);
        raiz.querySelector('[data-contador-conceptos]').textContent = `${filas.length} ${filas.length === 1 ? 'concepto' : 'conceptos'}`;
    }

    function validar() {
        const errores = [];
        if (!formulario.querySelector('[data-cliente-id]').value) {
            errores.push('Selecciona un cliente de la lista de resultados.');
        }
        if (filas.length === 0 || filas.every((fila) => !fila.elemento)) {
            errores.push('Agrega al menos un producto o servicio.');
        }
        filas.forEach((fila, indice) => {
            if (!fila.elemento) {
                errores.push(`Selecciona el concepto de la fila ${indice + 1}.`);
            }
            if (!fila.descripcion.value.trim()) {
                errores.push(`Escribe la descripción de la fila ${indice + 1}.`);
            }
            if (Number(fila.cantidad.value) <= 0) {
                errores.push(`La cantidad de la fila ${indice + 1} debe ser mayor que cero.`);
            }
            if (Number(fila.precio.value) <= 0) {
                errores.push(`El precio de la fila ${indice + 1} debe ser mayor que cero.`);
            }
            if (Number(fila.descuento.value) < 0 || Number(fila.descuento.value) > 100) {
                errores.push(`El descuento de la fila ${indice + 1} debe estar entre 0 y 100%.`);
            }
        });
        return errores;
    }

    function mostrarErrores(errores) {
        if (errores.length === 0) {
            contenedorErrores.hidden = true;
            contenedorErrores.replaceChildren();
            return;
        }
        const titulo = document.createElement('strong');
        titulo.textContent = 'Revisa la información antes de guardar:';
        const lista = document.createElement('ul');
        errores.forEach((error) => {
            const item = document.createElement('li');
            item.textContent = error;
            lista.append(item);
        });
        contenedorErrores.replaceChildren(titulo, lista);
        contenedorErrores.hidden = false;
        contenedorErrores.scrollIntoView({behavior: 'smooth', block: 'center'});
    }

    function payload() {
        const datosFormulario = new FormData(formulario);
        return {
            cliente_id: Number(datosFormulario.get('cliente_id')),
            area_solicitante: datosFormulario.get('area_solicitante') || null,
            notas: datosFormulario.get('notas') || null,
            porcentaje_descuento: Number(datosFormulario.get('porcentaje_descuento')) || 0,
            items: filas.map((fila) => ({
                tipo: fila.tipo.value,
                producto_id: fila.tipo.value === 'producto' ? fila.elemento?.id : null,
                servicio_id: fila.tipo.value === 'servicio' ? fila.elemento?.id : null,
                descripcion: fila.descripcion.value.trim(),
                cantidad: Number(fila.cantidad.value),
                precio_unitario: Number(fila.precio.value),
                porcentaje_descuento: Number(fila.descuento.value),
            })),
        };
    }

    formulario.addEventListener('submit', async (evento) => {
        evento.preventDefault();
        const errores = validar();
        mostrarErrores(errores);
        if (errores.length > 0) {
            return;
        }

        botonGuardar.disabled = true;
        botonGuardar.textContent = 'Guardando…';
        try {
            const respuesta = await fetch(raiz.dataset.url, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': formulario.querySelector('[name="_token"]').value,
                },
                body: JSON.stringify(payload()),
            });
            const resultado = await respuesta.json();
            if (!respuesta.ok) {
                const mensajes = resultado.errors
                    ? Object.values(resultado.errors).flat()
                    : [resultado.message || 'No se pudo guardar la cotización.'];
                mostrarErrores(mensajes);
                return;
            }
            window.location.assign(resultado.redireccion);
        } catch (error) {
            mostrarErrores(['No fue posible comunicarse con el servidor. Intenta nuevamente.']);
        } finally {
            botonGuardar.disabled = false;
            botonGuardar.textContent = 'Guardar cotización';
        }
    });

    botonAgregar.addEventListener('click', crearFila);
    descuentoGeneral.addEventListener('input', calcularTotales);
    configurarCliente();
    crearFila();
})();
