(() => {
    const raiz = document.querySelector('[data-creador-cotizacion]');

    if (!raiz) {
        return;
    }

    const datos = JSON.parse(raiz.querySelector('[data-datos-cotizacion]').textContent);
    const formulario = raiz.querySelector('[data-formulario-cotizacion]');
    const cuerpo = raiz.querySelector('[data-filas-cotizacion]');
    const botonesAgregar = raiz.querySelectorAll('[data-agregar-concepto], [data-agregar-concepto-otro]');
    const vacioConceptos = raiz.querySelector('[data-conceptos-vacio]');
    const tablaConceptos = raiz.querySelector('[data-tabla-conceptos]');
    const botonGuardar = raiz.querySelector('[data-guardar-cotizacion]');
    const descuentoGeneral = raiz.querySelector('[name="porcentaje_descuento"]');
    const contenedorErrores = raiz.querySelector('.creador-cotizacion__errores');
    const botonAbrirPlantillas = raiz.querySelector('[data-abrir-plantillas-pc]');
    const botonAbrirPersonalizar = raiz.querySelector('[data-abrir-personalizar-pc]');
    const dialogoPc = raiz.querySelector('[data-dialogo-pc-armada]');
    const dialogoTitulo = raiz.querySelector('[data-dialogo-titulo]');
    const dialogoInstruccion = raiz.querySelector('[data-dialogo-instruccion]');
    const dialogoPlantillas = raiz.querySelector('[data-dialogo-plantillas-pc]');
    const dialogoEditor = raiz.querySelector('[data-dialogo-editor-pc]');
    const opcionesPlantillas = raiz.querySelector('[data-plantillas-pc-opciones]');
    const vacioPlantillas = raiz.querySelector('[data-plantillas-pc-vacio]');
    const editorPc = raiz.querySelector('[data-editor-pc]');
    const resumenPcs = raiz.querySelector('[data-pcs-resumen]');
    const vacioPcs = raiz.querySelector('[data-pcs-vacio]');
    const botonCerrarDialogo = raiz.querySelector('[data-cerrar-dialogo-pc]');
    const botonCancelarEdicion = raiz.querySelector('[data-cancelar-edicion-pc]');
    const botonGuardarEdicion = raiz.querySelector('[data-guardar-edicion-pc]');
    const filas = [];
    const pcs = [];
    let consecutivo = 0;
    let editorActivo = null;
    let invocadorDialogo = null;

    const moneda = new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN',
    });

    const normalizar = (valor) => String(valor ?? '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase();

    function limitarAEnteros(control) {
        control.addEventListener('keydown', (evento) => {
            if (['.', ',', 'e', 'E', '+', '-'].includes(evento.key)) {
                evento.preventDefault();
            }
        });
        control.addEventListener('paste', (evento) => {
            const texto = evento.clipboardData?.getData('text').trim() ?? '';
            if (!/^\d+$/.test(texto)) {
                evento.preventDefault();
            }
        });
    }

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

    function actualizarVisibilidadConceptos() {
        const hayFilas = filas.length > 0;
        vacioConceptos.hidden = hayFilas;
        tablaConceptos.hidden = !hayFilas;
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
            <td data-label="Descripción del catálogo"><textarea class="captura-descripcion" rows="2" aria-label="Descripción del catálogo" readonly></textarea></td>
            <td data-label="Cantidad"><input class="captura-cantidad" type="number" min="1" step="1" value="1" inputmode="numeric" aria-label="Cantidad"></td>
            <td data-label="Precio unitario"><input class="captura-precio" type="number" min="0.01" step="0.01" value="0.00" aria-label="Precio unitario" readonly></td>
            <td data-label="Descuento (%)"><input class="captura-descuento" type="number" min="0" max="100" step="1" value="0" inputmode="numeric" aria-label="Descuento porcentual"></td>
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
        actualizarVisibilidadConceptos();
        limitarAEnteros(estado.cantidad);
        limitarAEnteros(estado.descuento);

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
            estado.descripcion.value = '';
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

    const etiquetasComponentesModal = {
        procesador: 'Procesador',
        placa_madre: 'Placa madre',
        memoria_ram: 'Memoria RAM',
        almacenamiento: 'Almacenamiento',
        tarjeta_grafica: 'Tarjeta gráfica',
        fuente_poder: 'Fuente de poder',
        gabinete: 'Gabinete',
        enfriamiento: 'Enfriamiento',
        sistema_operativo: 'Sistema operativo',
        accesorio: 'Accesorios',
    };
    const componentesBaseModal = [
        'procesador',
        'placa_madre',
        'memoria_ram',
        'almacenamiento',
        'tarjeta_grafica',
        'fuente_poder',
        'gabinete',
    ];
    const componentesExtraModal = ['enfriamiento', 'sistema_operativo', 'accesorio'];

    function normalizarComponentesModal(componentes = []) {
        return componentes.map((componente) => ({
            producto_id: Number(componente.producto_id),
            tipo: componente.tipo_componente_pc || componente.tipo,
            cantidad: Number(componente.cantidad) || 1,
        }));
    }

    function productoComponenteModal(componente) {
        return datos.catalogo.find((producto) => String(producto.id) === String(componente.producto_id));
    }

    function totalComponentesModal(componentes) {
        return componentes.reduce((total, componente) => {
            const producto = productoComponenteModal(componente);
            return total + (producto?.precio || 0) * (Number(componente.cantidad) || 0);
        }, 0);
    }

    function resumenComponentesModal(componentes) {
        const nombres = componentes
            .map((componente) => productoComponenteModal(componente)?.nombre)
            .filter(Boolean);
        const visibles = nombres.slice(0, 4).join(' · ');
        const restantes = nombres.length - 4;

        return restantes > 0 ? `${visibles} · +${restantes} más` : visibles || 'Sin componentes seleccionados';
    }

    function actualizarResumenPcModal(estado) {
        if (!estado.tarjeta) {
            estado.tarjeta = document.createElement('article');
            estado.tarjeta.className = 'cotizacion-pc-resumen';
            resumenPcs.append(estado.tarjeta);
        }
        estado.tarjeta.innerHTML = `
            <div class="cotizacion-pc-resumen__encabezado">
                <div>
                    <p class="ceja-seccion" data-pc-tipo></p>
                    <h4 data-pc-nombre></h4>
                    <p class="texto-ayuda" data-pc-descripcion></p>
                </div>
                <div class="cotizacion-pc-resumen__importe">
                    <strong data-pc-total></strong>
                    <div class="grupo-acciones">
                        ${estado.esPlantilla ? '' : '<button class="boton boton--contorno boton--compacto" type="button" data-editar-pc>Editar</button>'}
                        <button class="boton boton--peligro boton--compacto" type="button" data-eliminar-pc>Quitar</button>
                    </div>
                </div>
            </div>
            <p class="cotizacion-pc-resumen__componentes" data-pc-componentes></p>`;
        estado.tarjeta.querySelector('[data-pc-tipo]').textContent = estado.esPlantilla ? 'Plantilla' : 'Personalizada';
        estado.tarjeta.querySelector('[data-pc-nombre]').textContent = estado.nombre;
        estado.tarjeta.querySelector('[data-pc-descripcion]').textContent = estado.descripcion || 'Configuración de componentes para esta cotización.';
        estado.tarjeta.querySelector('[data-pc-total]').textContent = `${estado.cantidad} PC${estado.cantidad === 1 ? '' : 's'} · ${moneda.format(totalComponentesModal(estado.componentes) * estado.cantidad)}`;
        estado.tarjeta.querySelector('[data-pc-componentes]').textContent = resumenComponentesModal(estado.componentes);
        estado.tarjeta.querySelector('[data-eliminar-pc]').addEventListener('click', () => {
            estado.tarjeta.remove();
            pcs.splice(pcs.indexOf(estado), 1);
            actualizarEstadoPcsModal();
            calcularTotales();
        });
        estado.tarjeta.querySelector('[data-editar-pc]')?.addEventListener('click', (evento) => {
            abrirEditorPcModal(estado, evento.currentTarget);
        });
    }

    function actualizarEstadoPcsModal() {
        vacioPcs.hidden = pcs.length > 0;
        raiz.querySelector('[data-contador-pcs]').textContent = pcs.length;
    }

    function crearPc(configuracion = {}, existente = null) {
        const estado = existente || {
            id: `pc-${Date.now()}-${pcs.length}`,
            tarjeta: null,
        };
        estado.esPlantilla = Boolean(configuracion.plantilla_id);
        estado.plantillaId = configuracion.plantilla_id || null;
        estado.nombre = configuracion.nombre || 'PC personalizada';
        estado.descripcion = configuracion.descripcion || '';
        estado.cantidad = Math.max(Number(configuracion.cantidad) || 1, 1);
        estado.componentes = normalizarComponentesModal(configuracion.componentes);
        if (!existente) {
            pcs.push(estado);
        }
        actualizarResumenPcModal(estado);
        actualizarEstadoPcsModal();
        calcularTotales();
        return estado;
    }

    function crearFilaEditorPcModal(tipo, componente = {}, puedeQuitar = false) {
        const fila = document.createElement('div');
        fila.className = 'pc-configuracion__fila';
        fila.dataset.editorComponente = 'true';
        fila.innerHTML = `
            <input type="hidden" value="${tipo}" data-pc-tipo>
            <select aria-label="Producto de ${etiquetasComponentesModal[tipo]}" data-pc-producto></select>
            <input type="number" min="1" step="1" value="${componente.cantidad || 1}" aria-label="Cantidad de ${etiquetasComponentesModal[tipo]}" data-pc-cantidad>
            ${puedeQuitar ? '<button type="button" class="boton boton--peligro boton--compacto" data-eliminar-componente-pc>Quitar</button>' : ''}`;
        const producto = fila.querySelector('[data-pc-producto]');
        producto.append(new Option('Selecciona un producto', ''));
        datos.catalogo
            .filter((elemento) => elemento.tipo === 'producto' && elemento.tipo_componente_pc === tipo)
            .forEach((elemento) => producto.append(new Option(`${elemento.nombre} (${elemento.codigo})`, elemento.id)));
        producto.value = componente.producto_id || '';
        producto.addEventListener('change', actualizarTotalEditorPcModal);
        fila.querySelector('[data-pc-cantidad]').addEventListener('input', actualizarTotalEditorPcModal);
        fila.querySelector('[data-eliminar-componente-pc]')?.addEventListener('click', () => {
            fila.remove();
            actualizarTotalEditorPcModal();
        });
        return fila;
    }

    function crearApartadoEditorPcModal(contenedor, tipo, ayuda, componentes, puedeQuitar = false) {
        const apartado = document.createElement('section');
        apartado.className = 'pc-configuracion__apartado';
        apartado.innerHTML = `
            <div class="pc-configuracion__apartado-cabecera">
                <div><h4>${etiquetasComponentesModal[tipo]}</h4><p>${ayuda}</p></div>
            </div>
            <div class="pc-configuracion__filas"></div>`;
        contenedor.append(apartado);
        const filasApartado = apartado.querySelector('.pc-configuracion__filas');
        const agregar = (componente = {}) => filasApartado.append(
            crearFilaEditorPcModal(tipo, componente, puedeQuitar)
        );
        componentes.forEach(agregar);
        if (componentes.length === 0) {
            agregar();
        }
        return {apartado, agregar};
    }

    function renderizarEditorPcModal(configuracion = {}) {
        editorPc.replaceChildren();
        editorPc.innerHTML = `
            <div class="rejilla-formulario">
                <div class="campo-formulario"><label for="nombre-pc-dialogo">Nombre de la PC</label><input id="nombre-pc-dialogo" maxlength="255" data-editor-nombre></div>
                <div class="campo-formulario"><label for="cantidad-pc-dialogo">Cantidad de equipos</label><input id="cantidad-pc-dialogo" type="number" min="1" max="100" step="1" value="1" data-editor-cantidad></div>
                <div class="campo-formulario"><label for="descripcion-pc-dialogo">Descripción de la PC</label><textarea id="descripcion-pc-dialogo" rows="2" maxlength="2000" data-editor-descripcion></textarea></div>
            </div>
            <div class="alerta alerta--peligro" role="alert" data-editor-errores hidden></div>
            <div class="pc-configuracion__componentes" data-editor-componentes></div>
            <p class="pc-configuracion__total">Total estimado: <strong data-editor-total>$0.00</strong></p>`;
        editorPc.querySelector('[data-editor-nombre]').value = configuracion.nombre || 'PC personalizada';
        editorPc.querySelector('[data-editor-cantidad]').value = Math.max(Number(configuracion.cantidad) || 1, 1);
        editorPc.querySelector('[data-editor-descripcion]').value = configuracion.descripcion || '';

        const componentes = normalizarComponentesModal(configuracion.componentes);
        const porTipo = componentes.reduce((grupos, componente) => {
            const grupo = grupos.get(componente.tipo) || [];
            grupo.push(componente);
            grupos.set(componente.tipo, grupo);
            return grupos;
        }, new Map());
        const contenedor = editorPc.querySelector('[data-editor-componentes]');
        componentesBaseModal.forEach((tipo) => crearApartadoEditorPcModal(
            contenedor,
            tipo,
            'Selecciona un producto del inventario o déjalo vacío si no se requiere.',
            (porTipo.get(tipo) || []).slice(0, 1)
        ));
        const extras = document.createElement('details');
        extras.className = 'pc-configuracion__extras';
        extras.open = componentesExtraModal.some((tipo) => porTipo.has(tipo));
        extras.innerHTML = '<summary>Extras opcionales</summary><p class="texto-ayuda">Agrega enfriamiento, sistema operativo, monitor, teclado, mouse u otro accesorio.</p><div class="pc-configuracion__extras-contenido"></div>';
        contenedor.append(extras);
        const extrasContenido = extras.querySelector('.pc-configuracion__extras-contenido');
        componentesExtraModal.forEach((tipo) => {
            const apartado = crearApartadoEditorPcModal(
                extrasContenido,
                tipo,
                tipo === 'accesorio' ? 'Puedes agregar monitor, teclado, mouse, bocinas u otros accesorios.' : 'Es opcional; agrégalo solo si forma parte de esta PC.',
                porTipo.get(tipo) || [],
                true
            );
            const boton = document.createElement('button');
            boton.type = 'button';
            boton.className = 'boton boton--contorno boton--compacto';
            boton.textContent = `+ Agregar otro ${etiquetasComponentesModal[tipo].toLowerCase()}`;
            boton.addEventListener('click', () => apartado.agregar());
            apartado.apartado.append(boton);
        });
        actualizarTotalEditorPcModal();
        editorPc.querySelector('[data-editor-nombre]').focus();
    }

    function componentesEditorPcModal() {
        return [...editorPc.querySelectorAll('[data-editor-componente]')]
            .map((fila) => ({
                producto_id: Number(fila.querySelector('[data-pc-producto]').value),
                tipo: fila.querySelector('[data-pc-tipo]').value,
                cantidad: Number(fila.querySelector('[data-pc-cantidad]').value),
            }))
            .filter((componente) => componente.producto_id);
    }

    function actualizarTotalEditorPcModal() {
        editorPc?.querySelector('[data-editor-total]')?.replaceChildren(
            moneda.format(totalComponentesModal(componentesEditorPcModal()))
        );
    }

    function mostrarErrorEditorPcModal(mensajes) {
        const contenedor = editorPc.querySelector('[data-editor-errores]');
        if (mensajes.length === 0) {
            contenedor.hidden = true;
            contenedor.replaceChildren();
            return;
        }
        const lista = document.createElement('ul');
        mensajes.forEach((mensaje) => {
            const item = document.createElement('li');
            item.textContent = mensaje;
            lista.append(item);
        });
        contenedor.replaceChildren(lista);
        contenedor.hidden = false;
    }

    function abrirDialogoPcModal(invocador) {
        invocadorDialogo = invocador;
        if (typeof dialogoPc.showModal === 'function' && !dialogoPc.open) {
            dialogoPc.showModal();
        } else {
            dialogoPc.hidden = false;
            dialogoPc.setAttribute('open', '');
        }
    }

    function finalizarDialogoPcModal() {
        dialogoPlantillas.hidden = true;
        dialogoEditor.hidden = true;
        editorPc.replaceChildren();
        editorActivo = null;
        const invocador = invocadorDialogo;
        invocadorDialogo = null;
        invocador?.focus();
    }

    function cerrarDialogoPcModal() {
        if (typeof dialogoPc.close === 'function' && dialogoPc.open) {
            dialogoPc.close();
            return;
        }
        dialogoPc.hidden = true;
        dialogoPc.removeAttribute('open');
        finalizarDialogoPcModal();
    }

    function abrirPlantillasPcModal(invocador) {
        dialogoTitulo.textContent = 'Agregar PC de plantilla';
        dialogoInstruccion.textContent = 'Elige una configuración guardada para agregarla a la cotización.';
        dialogoPlantillas.hidden = false;
        dialogoEditor.hidden = true;
        opcionesPlantillas.replaceChildren();
        vacioPlantillas.hidden = datos.pcArmadas.length > 0;
        datos.pcArmadas.forEach((plantilla) => {
            const tarjeta = document.createElement('article');
            tarjeta.className = 'pc-plantilla-opcion';
            const nombre = document.createElement('h3');
            nombre.textContent = plantilla.nombre;
            const descripcion = document.createElement('p');
            descripcion.textContent = plantilla.descripcion || 'Configuración guardada de componentes.';
            const componentes = document.createElement('p');
            componentes.className = 'texto-ayuda';
            componentes.textContent = resumenComponentesModal(normalizarComponentesModal(plantilla.componentes));
            const boton = document.createElement('button');
            boton.type = 'button';
            boton.className = 'boton boton--secundario';
            boton.textContent = 'Agregar esta PC';
            boton.addEventListener('click', () => {
                const cantidad = Number(cantidadControl.value);
                crearPc({
                    nombre: plantilla.nombre,
                    descripcion: plantilla.descripcion,
                    plantilla_id: plantilla.id,
                    cantidad,
                    componentes: plantilla.componentes,
                });
                cerrarDialogoPcModal();
            });
            const cantidadEtiqueta = document.createElement('label');
            cantidadEtiqueta.textContent = 'Cantidad de equipos';
            const cantidadControl = document.createElement('input');
            cantidadControl.type = 'number';
            cantidadControl.min = '1';
            cantidadControl.max = '100';
            cantidadControl.step = '1';
            cantidadControl.value = '1';
            cantidadControl.className = 'control-cantidad-pc';
            cantidadEtiqueta.append(cantidadControl);
            tarjeta.append(nombre, descripcion, componentes, cantidadEtiqueta, boton);
            opcionesPlantillas.append(tarjeta);
        });
        abrirDialogoPcModal(invocador);
    }

    function abrirEditorPcModal(estado = null, invocador = botonAbrirPersonalizar) {
        editorActivo = {estado};
        dialogoTitulo.textContent = estado ? 'Editar PC personalizada' : 'Personalizar PC';
        dialogoInstruccion.textContent = 'Selecciona los componentes que formarán parte de esta PC.';
        dialogoPlantillas.hidden = true;
        dialogoEditor.hidden = false;
        renderizarEditorPcModal(estado || {});
        abrirDialogoPcModal(invocador);
    }

    function guardarEditorPcModal() {
        const nombre = editorPc.querySelector('[data-editor-nombre]').value.trim();
        const cantidad = Number(editorPc.querySelector('[data-editor-cantidad]').value);
        const descripcion = editorPc.querySelector('[data-editor-descripcion]').value.trim();
        const componentes = componentesEditorPcModal();
        const errores = [];
        if (!nombre) {
            errores.push('Escribe el nombre de la PC.');
        }
        if (!Number.isInteger(cantidad) || cantidad < 1 || cantidad > 100) {
            errores.push('La cantidad de equipos debe ser un entero entre 1 y 100.');
        }
        if (componentes.length === 0) {
            errores.push('Selecciona al menos un componente para la PC.');
        }
        componentes.forEach((componente, indice) => {
            if (!Number.isInteger(componente.cantidad) || componente.cantidad <= 0) {
                errores.push(`La cantidad del componente ${indice + 1} debe ser un entero positivo.`);
            }
        });
        mostrarErrorEditorPcModal(errores);
        if (errores.length > 0) {
            return;
        }
        crearPc({nombre, descripcion, cantidad, componentes}, editorActivo.estado);
        cerrarDialogoPcModal();
    }

    function importePcs() {
        return pcs.reduce((totalPc, pc) => totalPc + pc.componentes.reduce((total, componente) => {
            const productoId = componente.producto_id ?? componente.producto?.id ?? componente.querySelector?.('[data-pc-producto]')?.value;
            const cantidad = componente.cantidad ?? componente.querySelector?.('[data-pc-cantidad]')?.value;
            const producto = datos.catalogo.find((elemento) => String(elemento.id) === String(productoId));
            return total + (producto?.precio || 0) * (Number(cantidad) || 0);
        }, 0) * pc.cantidad, 0);
    }

    function calcularTotales() {
        const importes = filas.map((fila) => {
            const importe = importeFila(fila);
            fila.subtotal.textContent = moneda.format(importe.total);
            return importe;
        });
        const bruto = importes.reduce((suma, importe) => suma + importe.bruto, 0);
        const descuentoLineas = importes.reduce((suma, importe) => suma + importe.descuento, 0);
        const brutoPc = importePcs();
        const brutoTotal = bruto + brutoPc;
        const totalLineas = brutoTotal - descuentoLineas;
        const porcentajeGeneral = Math.min(Math.max(Number(descuentoGeneral.value) || 0, 0), 10);
        const descuento = descuentoLineas + totalLineas * porcentajeGeneral / 100;
        const total = brutoTotal - descuento;
        const subtotalSinIva = total / 1.16;
        const iva = total - subtotalSinIva;

        raiz.querySelector('[data-total-bruto]').textContent = moneda.format(brutoTotal);
        raiz.querySelector('[data-total-descuento]').textContent = `−${moneda.format(descuento)}`;
        raiz.querySelector('[data-total-subtotal]').textContent = moneda.format(subtotalSinIva);
        raiz.querySelector('[data-total-iva]').textContent = moneda.format(iva);
        raiz.querySelector('[data-total-final]').textContent = moneda.format(total);
        raiz.querySelector('[data-contador-conceptos]').textContent = filas.length;
        actualizarVisibilidadConceptos();
        pcs.forEach((pc) => {
            const total = pc.componentes.reduce((suma, componente) => {
                const productoId = componente.producto_id ?? componente.querySelector?.('[data-pc-producto]')?.value;
                const cantidad = componente.cantidad ?? componente.querySelector?.('[data-pc-cantidad]')?.value;
                const producto = datos.catalogo.find((elemento) => String(elemento.id) === String(productoId));
                return suma + (producto?.precio || 0) * (Number(cantidad) || 0);
            }, 0) * pc.cantidad;
            pc.tarjeta.querySelector('[data-pc-total]').textContent = `${pc.cantidad} PC${pc.cantidad === 1 ? '' : 's'} · ${moneda.format(total)}`;
        });
    }

    function validar() {
        const errores = [];
        if (!formulario.querySelector('[data-cliente-id]').value) {
            errores.push('Selecciona un cliente de la lista de resultados.');
        }
        if (filas.every((fila) => !fila.elemento) && pcs.length === 0) {
            errores.push('Agrega al menos un producto, servicio o PC personalizada.');
        }
        filas.forEach((fila, indice) => {
            if (!fila.elemento) {
                if (pcs.length === 0) {
                    errores.push(`Selecciona el concepto de la fila ${indice + 1}.`);
                }
                return;
            }
            if (!fila.descripcion.value.trim()) {
                errores.push(`Escribe la descripción de la fila ${indice + 1}.`);
            }
            if (Number(fila.cantidad.value) <= 0) {
                errores.push(`La cantidad de la fila ${indice + 1} debe ser mayor que cero.`);
            } else if (!Number.isInteger(Number(fila.cantidad.value))) {
                errores.push(`La cantidad de la fila ${indice + 1} debe ser un número entero.`);
            }
            if (Number(fila.precio.value) <= 0) {
                errores.push(`El precio de la fila ${indice + 1} debe ser mayor que cero.`);
            }
            if (Number(fila.descuento.value) < 0 || Number(fila.descuento.value) > 100) {
                errores.push(`El descuento de la fila ${indice + 1} debe estar entre 0 y 100%.`);
            } else if (!Number.isInteger(Number(fila.descuento.value))) {
                errores.push(`El descuento de la fila ${indice + 1} debe ser un número entero.`);
            }
        });
        pcs.forEach((pc, indicePc) => {
            if (!pc.nombre.trim()) {
                errores.push(`Escribe el nombre de la PC ${indicePc + 1}.`);
            }
            if (pc.componentes.length === 0) {
                errores.push(`Selecciona al menos un componente para la PC ${indicePc + 1}.`);
            }
            pc.componentes.forEach((componente, indiceComponente) => {
                if (!Number.isInteger(Number(componente.cantidad)) || Number(componente.cantidad) <= 0) {
                    errores.push(`La cantidad del componente seleccionado ${indiceComponente + 1} de la PC ${indicePc + 1} debe ser un entero positivo.`);
                }
            });
        });
        if (!Number.isInteger(Number(descuentoGeneral.value))) {
            errores.push('El descuento general debe ser un número entero.');
        }
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
            items: filas.filter((fila) => fila.elemento).map((fila) => ({
                tipo: fila.tipo.value,
                producto_id: fila.tipo.value === 'producto' ? fila.elemento?.id : null,
                servicio_id: fila.tipo.value === 'servicio' ? fila.elemento?.id : null,
                descripcion: fila.descripcion.value.trim(),
                cantidad: Number(fila.cantidad.value),
                precio_unitario: Number(fila.precio.value),
                porcentaje_descuento: Number(fila.descuento.value),
            })),
            pc_armadas: pcs.map((pc) => ({
                nombre: pc.nombre.trim(),
                descripcion: pc.descripcion.trim() || null,
                cantidad: pc.cantidad,
                plantilla_id: pc.plantillaId,
                componentes: pc.componentes.map((componente) => ({
                    producto_id: Number(componente.producto_id),
                    tipo_componente_pc: componente.tipo,
                    cantidad: Number(componente.cantidad),
                })),
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

    botonesAgregar.forEach((boton) => boton.addEventListener('click', crearFila));
        botonAbrirPlantillas.addEventListener('click', () => abrirPlantillasPcModal(botonAbrirPlantillas));
        botonAbrirPersonalizar.addEventListener('click', () => abrirEditorPcModal());
        botonCerrarDialogo.addEventListener('click', cerrarDialogoPcModal);
        botonCancelarEdicion.addEventListener('click', cerrarDialogoPcModal);
        botonGuardarEdicion.addEventListener('click', guardarEditorPcModal);
        dialogoPc.addEventListener('close', finalizarDialogoPcModal);
    descuentoGeneral.addEventListener('input', calcularTotales);
    limitarAEnteros(descuentoGeneral);
    configurarCliente();
    calcularTotales();
})();
