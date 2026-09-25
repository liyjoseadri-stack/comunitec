# Seguimiento de Comunitec

## Avance del 24 de septiembre de 2026

- [x] Cerrar la Entrega 3: folio único, borradores, partidas de catálogo y libres, cálculos del servidor, descuento global, advertencias de inventario y conservación de precios históricos.
- [x] Impedir que el navegador altere el precio de productos o servicios: al agregar se copia el precio vigente del catálogo. Al editar una partida se conserva el precio cotizado aunque cambie o se desactive el artículo. Los artículos desactivados no pueden agregarse a nuevas partidas.
- Verificación de cierre: 58 pruebas, 267 aserciones, Pint y Blade aprobados; auditorías de Composer y NPM sin vulnerabilidades conocidas. Vite no pudo ejecutarse dentro del sandbox por acceso denegado al resolver `vite.config.js`; los cambios finales no afectan recursos procesados por Vite y existe una compilación local previa aportada por el usuario.
- [x] Confirmar recepción de los dos mensajes de prueba: confirmación explícita del usuario. La apertura del PDF no se ha confirmado explícitamente.
- [x] Corregir cancelación y rechazo con estado desactualizado. La cancelación libera piezas si la versión vigente está aceptada; el rechazo no sobrescribe una aceptación posterior a la lectura inicial.
- Verificación: las dos regresiones fallaban antes de corregir el controlador y ahora pasan. Suite completa: 54 pruebas, 255 aserciones; formato PHP correcto. No se ejecutó prueba de concurrencia real en MySQL. Pendiente recorrido interactivo completo y cierre de entrega.

Este archivo refleja el plan aprobado en `tasks/plan.md`. Una tarea se marca al completar su criterio de aceptación y registrar evidencia.

## Avance verificado del 23 de septiembre de 2026

- [x] Configurar Gmail mediante TLS implícito en el puerto 465 y comprobar autenticación y aceptación SMTP real. Dos mensajes enviados a la propia cuenta autorizada: texto y cotización ficticia con PDF. Sin persistir datos comerciales. Credenciales solo en `.env`, ignorado por Git.
- [x] Confirmar recepción de los dos mensajes en la bandeja del destinatario. La apertura del PDF no fue confirmada explícitamente.
- Pruebas del módulo de correo tras configurar SMTP: 5 aprobadas, 27 aserciones; usan transporte de pruebas. La configuración real sustituye el modo `log` mencionado en avances anteriores.
- [x] Revisar presentación del detalle con una partida ficticia: encabezado editable, etiquetas del alta, edición desplegable y tabla adaptable mediante desplazamiento interno. Verificación en navegador a 454 y 1280 px; sin registros nuevos en MySQL. Pruebas posteriores: 52 aprobadas, 249 aserciones, formato PHP y Blade correctos. Queda pendiente el recorrido interactivo completo de altas y transiciones contra el servidor, y SMTP real.
- [x] Revisar visualmente los listados de Cotizaciones e Inventario en navegador local. Corregidos espaciado, etiquetas, formularios y mensajes de ausencia de registros con estilos compartidos en `public/css/administracion.css`. Sin compilación Vite requerida.
- [x] Verificar acceso desde el menú, tabla vacía de inventario y ausencia de desbordamiento horizontal del listado de cotizaciones a 1280 px. Sin errores o advertencias de consola capturados. Falta probar visualmente cotizaciones con partidas; no se crearon registros comerciales para esta revisión.
- SMTP real configurado posteriormente con una cuenta autorizada; el usuario confirmó la recepción de los dos mensajes de prueba.
- [x] Mostrar errores en el formulario de creación de cotizaciones y conservar los datos capturados para corregirlos. Prueba de regresión con descuento inválido: mensaje visible y valores conservados.
- Verificación: 52 pruebas y 249 aserciones aprobadas; formato PHP y compilación de vistas correctos. Este avance no cierra la entrega 3; siguen pendientes la revisión visual y la verificación del correo con SMTP real.

## Entrega 0: base reproducible

- [x] Verificar PHP, Composer, Node, MySQL y extensiones requeridas.
- [x] Confirmar `.env` local y una base MySQL de desarrollo aislada en el puerto 3307, sin secretos versionados.
- [x] Ejecutar migraciones, pruebas iniciales y compilación frontend.

## Antes de la Entrega 1

- [ ] Confirmar formato de folio y moneda visible.
- [ ] Reunir datos corporativos y logo para el PDF.
- [x] Aprobar la especificación de datos y permisos en `tasks/entrega-1-datos-y-permisos.md`.
- [ ] Diseñar migraciones y matriz de permisos.

## Entrega 1: acceso y roles

- [x] Añadir los roles base `admin`, `comercial` y `consulta` al modelo de usuario.
- [x] Implementar inicio y cierre de sesión, con bloqueo de usuarios desactivados.
- [x] Aplicar la primera protección de ruta por rol: administración de usuarios exclusiva para Administrador.
- [x] Crear listado y altas de usuarios para el rol Administrador.
- [x] Permitir activar o desactivar usuarios desde la administración.
- [x] Añadir formulario de edición de nombre, correo, contraseña y rol.

## Entrega 2: clientes, catálogo e inventario

- [x] Registrar y editar clientes físicos y morales.
- [x] Registrar categorías, productos y servicios.
- [x] Registrar piezas mediante número de serie y mostrar alertas de stock mínimo.

## Entrega 3 completada y avances del ciclo de cotizaciones

- [x] Crear cotizaciones en borrador con cliente, área solicitante y descuento global.
- [x] Agregar y eliminar partidas de producto, servicio u otros y recalcular el total en el servidor.
- [x] Advertir en el borrador cuando las piezas cotizadas no están disponibles, sin bloquearlo.
- [x] Mostrar estados, catálogo e inventario en español.
- [x] Generar y descargar PDF de cotización.
- [x] Enviar manualmente la cotización por correo con PDF adjunto, usando la configuración de correo de Laravel.
- [x] Vencer cotizaciones pendientes a los 15 días.
- [x] Reservar piezas al aceptar, bloquear faltantes y liberar piezas al cancelar o después de 5 días.
- [x] Permitir editar las partidas de una cotización aceptada y devolverla a Pendiente liberando sus reservas. Los datos inválidos conservan la aceptación y las reservas.
- [x] Editar cliente, área y descuento; recalcular el total en servidor y liberar reservas si se modifica una aceptada. Guardar sin cambios conserva su aceptación.
- [x] Aplicar el mismo rango de descuento al crear y editar: 0 sin descuento, o entre 5% y 10%.
- [x] Evitar colisiones de folios al crear varias cotizaciones en el mismo instante mediante un identificador ULID. El formato comercial definitivo sigue pendiente.
- [x] Sumar las partidas repetidas de un producto para advertir faltantes y verificar que una aceptación fallida no deja reservas parciales.
- [x] Comprobar estado y vigencia dentro de la transacción de reserva; impedir aceptación fuera de plazo y reservas duplicadas.
- [x] Entrega 5: convertir una cotización aceptada una sola vez en venta, registrar método de pago, copiar datos históricos y asignar las series entregadas sin descontar inventario nuevamente.
- [x] Configurar SMTP autorizado para entregas reales de correo.

## Entrega 4: ciclo de estados, PDF y correo

- [x] Aplicar las transiciones de Borrador, Pendiente, Aceptada, Rechazada, Cancelada y Vencida con permisos del servidor.
- [x] Vencer cotizaciones pendientes a los 15 días mediante el comando programado `cotizaciones:vencer`.
- [x] Permitir modificar cotizaciones aceptadas, liberar sus reservas y exigir una nueva aceptación.
- [x] Generar un PDF con cliente, responsable, vigencia, partidas, descuento, total e indicación de IVA incluido.
- [x] Enviar manualmente la cotización con el PDF adjunto y conservar estado y fechas cuando falla el transporte.
- [x] Registrar destinatario, usuario, fecha y resultado de cada intento de correo aceptado o fallido.
- [x] Mostrar el historial de correo aclarando que la aceptación del servicio no confirma recepción ni lectura.
- Verificación de cierre: 59 pruebas y 287 aserciones; Pint, compilación de Blade y programación diaria aprobados. La migración del historial se aplicó a MySQL local. El PDF se renderizó en A4 y se inspeccionó visualmente sin cortes ni desbordamientos.
## Entrega 5: ventas e inventario

- [x] Crear venta y partidas históricas vinculadas a la cotización aceptada.
- [x] Copiar cliente, responsable, importes y conceptos para que cambios posteriores no alteren la venta.
- [x] Registrar efectivo, transferencia, tarjeta u otro con descripción obligatoria.
- [x] Asignar exactamente una serie reservada por pieza y marcarla como entregada sin un segundo descuento.
- [x] Impedir ventas duplicadas, series ajenas o incompletas y registros parciales ante fallos.
- [x] Bloquear edición y cancelación de cotizaciones ya convertidas.
- [x] Permitir a Consulta listar y ver ventas sin acciones de escritura.
- [x] Mostrar en Inventario la venta y el cliente de cada serie entregada.
- Verificación de cierre: 74 pruebas y 419 aserciones; Pint, Blade, rutas, migraciones MySQL y revisión visual en escritorio y móvil aprobados.
- Siguiente entrega: panel, filtros, reportes y revisión integral de experiencia de uso.

## Entrega 6: panel y reportes

- [x] Mostrar el mes actual en el Panel y permitir recorrer o seleccionar otros meses.
- [x] Contar cotizaciones por Borrador, Pendiente, Aceptada, Rechazada, Cancelada y Vencida.
- [x] Calcular porcentaje de aceptación, cantidad de ventas e importe vendido del periodo.
- [x] Mostrar actividad reciente y alertas de inventario bajo.
- [x] Filtrar el reporte de cotizaciones por periodo, cliente, responsable y estado.
- [x] Filtrar el reporte de ventas por periodo, cliente, responsable y método de pago.
- [x] Conservar filtros al paginar y calcular totales sobre todos los resultados filtrados.
- [x] Permitir lectura de Panel y reportes a Administrador, Comercial y Consulta.
- [x] Recuperar nombres históricos faltantes de ventas antiguas sin sobrescribir copias existentes.
- [x] Revisar Panel y reportes en escritorio y móvil, incluidos foco, desplazamiento y consola.
- Verificación de cierre: 83 pruebas y 506 aserciones; Pint, Blade y revisión del diff aprobados. Revisión visual a 1280 × 800 y 390 × 844 sin desbordamiento del documento ni errores de consola. Vite quedó bloqueado dentro del sandbox por acceso denegado de `esbuild` a un directorio superior; las vistas nuevas usan CSS público directo.
- Siguiente entrega: cierre integral, documentación de operación y preparación de la entrega académica.

## Próximo punto activo

- Navegación común incorporada en Panel, Clientes, Catálogo, Inventario, listado/detalle de Cotizaciones y Usuarios. Se muestran enlaces por rol y se indica la sección actual con `aria-current`. Estilos en `public/css/navegacion.css`, sin recompilación de Vite.
- Verificación posterior: 51 pruebas y 241 aserciones aprobadas; Pint y compilación de Blade correctos. Los enlaces se verificaron mediante respuestas HTML; falta revisión visual en navegador antes del cierre de entrega.

- Vencimiento automático reforzado: se releen estado y vigencia con bloqueo antes de liberar reservas. Una copia antigua no cancela cotizaciones que volvieron a Pendiente ni reservas cuya vigencia se actualizó. El comando cuenta únicamente liberaciones efectivas.

- Permisos de cotizaciones verificados: Consulta puede listar, ver los seis estados y descargar PDF. No recibe formularios de escritura; creación, edición, partidas, envío y transiciones responden 403 ante solicitudes directas.
- Verificación posterior: 47 pruebas y 195 aserciones aprobadas, Pint aprobado y Blade compilado. Pendiente revisión integral de transiciones restantes, validación visual y configuración SMTP antes del cierre de entrega.

Revisar permisos y transiciones restantes. El envío de Borrador a Pendiente ya ocurre después de que el transporte acepta el mensaje con PDF; los errores de transporte conservan estado y fechas. Reenviar no extiende la vigencia. El modo `log` no se presenta como envío real. La conversión a venta corresponde al bloque posterior del plan.

## Entrega 8: traducción estructural al español

- [x] Crear rama y respaldo previo a la migración.
- [x] Definir y probar el contrato definitivo de tablas, columnas y valores en español.
- [x] Reiniciar usuarios y autenticación con autorización expresa para vaciar la base.
- [x] Traducir clientes, categorías, catálogo e inventario.
- [x] Traducir cotizaciones, partidas, envíos, estados y reservas.
- [x] Traducir ventas, partidas, copias históricas y métodos de pago.
- [x] Traducir variables, relaciones, parámetros, formularios y pruebas propios.
- [x] Sustituir las migraciones anteriores por un esquema nuevo completamente en español.
- [x] Ejecutar una migración limpia de MySQL y crear el administrador desde variables locales.
- [x] Ejecutar suite completa, Blade, formato, rutas, programación y revisión del esquema real.
- [x] Documentar únicamente las excepciones técnicas impuestas por Laravel y sus protocolos.
- [x] Crear el commit de Entrega 8 en español.
- [ ] Subir la rama de la Entrega 8 al remoto.

Verificación: MySQL quedó vacío y reconstruido con `migraciones`, `usuarios`, `clientes`, `categorias`, `articulos_catalogo`, `cotizaciones`, `partidas_cotizacion`, `piezas_inventario`, `envios_correo_cotizacion`, `ventas` y `partidas_venta`. Hay un administrador activo creado desde `.env`. Las 91 pruebas y 579 aserciones aprobaron; Pint, rutas, tarea programada y `git diff --check` aprobaron. Vite continúa bloqueado únicamente dentro del sandbox de Codex porque `esbuild` intenta leer un directorio superior; la compilación local anterior del usuario fue exitosa.

## Verificación del 23 de septiembre de 2026

- Corrección de legibilidad solicitada por el usuario: clases, arreglos, migraciones, pruebas, traducciones y plantillas expandidos con sangría. CSS del PDF estructurado. Revisión final sin líneas mayores de 180 caracteres en `app`, `database`, `routes`, `tests`, `lang` y `resources`.
- Formato comprobado con Laravel Pint; 45 pruebas y 160 aserciones siguen aprobadas tras el cambio de formato. Blade compila correctamente.

- Modelos, controladores, servicios, correos, vistas y pruebas propios renombrados al español; referencias y carga automática actualizadas.
- README y mensajes de validación en español; idioma local y de ejemplo configurado como `es`.
- Se mantienen nombres técnicos de Laravel y nombres históricos de tablas/migraciones para preservar compatibilidad con la base existente.
- `php artisan test`: 45 pruebas aprobadas, 160 aserciones. Incluyen generación del correo con PDF usando transporte en memoria, fallo de transporte, conservación de vigencia al reenviar y bloqueo del modo de registro. No se enviaron correos externos. Estas pruebas usan SQLite y no constituyen una prueba de concurrencia real con MySQL.
- Límite del envío: aceptación por el transporte no garantiza recepción en la bandeja del cliente. Correo y base de datos no comparten una transacción distribuida; un fallo de persistencia posterior al envío puede requerir conciliación antes de reintentar.
- `php artisan view:cache`: plantillas compiladas correctamente después de agregar el formulario de encabezado.
- El usuario aportó compilación local exitosa con Vite 7.3.6: 58 módulos, 1.84 segundos, archivos `app-1hg5XYzc.css` y `app-DMsN-rLE.js`. Esta evidencia corresponde al avance anterior al formulario de encabezado agregado en este turno. El aviso de actualización de npm no es un error.
- No se ha cerrado la entrega ni creado un commit de cierre en este avance.
