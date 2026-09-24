# Entrega 6: panel, reportes y experiencia de uso

## Objetivo

Dar a Administrador, Comercial y Consulta una vista mensual clara de la actividad comercial y permitirles investigar las operaciones que forman cada indicador. El panel abre con el mes actual y permite avanzar o retroceder entre meses. Los reportes detallados se mantienen separados del panel para evitar una página saturada.

## Alcance aprobado

- Mostrar las cotizaciones creadas durante el mes seleccionado y contarlas según su estado actual.
- Desglosar borradores, pendientes, aceptadas, rechazadas, canceladas y vencidas.
- Mostrar el porcentaje de aceptación como cotizaciones actualmente aceptadas entre todas las cotizaciones creadas en el periodo.
- Mostrar cantidad de ventas y total vendido usando la fecha de venta.
- Mostrar cotizaciones y ventas recientes del periodo.
- Mantener las alertas de productos con cinco piezas disponibles o menos.
- Permitir cambiar el mes y el año sin modificar datos.
- Proporcionar reportes separados de cotizaciones y ventas con filtros combinables.
- Permitir lectura a Administrador, Comercial y Consulta. Esta entrega no añade ninguna operación de escritura.
- No añadir exportación a PDF o Excel en esta entrega.

## Reglas de fechas e indicadores

El panel recibe `mes` con formato `AAAA-MM`. Si no se proporciona, usa el mes actual de la zona horaria configurada en Laravel. El intervalo comienza a las 00:00 del primer día y termina justo antes del primer día del mes siguiente, evitando errores por horas del último día.

Las cotizaciones pertenecen al mes según `quotes.created_at`. Su tarjeta de estado usa el valor actual de `quotes.status`, aunque la transición haya ocurrido después. El total mensual es la suma de todas las cotizaciones creadas dentro del intervalo. El porcentaje de aceptación es cero cuando no existen cotizaciones para evitar divisiones entre cero.

Las ventas pertenecen al mes según `sales.sold_at`. El importe vendido usa la suma de `sales.total`, que conserva el total histórico de cada operación. Los indicadores nunca se calculan desde el HTML ni desde colecciones parcialmente filtradas.

## Arquitectura

### Panel

Se reemplazará el cierre de ruta actual por `ControladorPanel`, responsable de validar el mes, construir el intervalo y consultar:

- total de cotizaciones;
- conteo agrupado por estado;
- porcentaje de aceptación;
- cantidad e importe de ventas;
- cinco cotizaciones y cinco ventas recientes del periodo;
- productos con inventario disponible igual o menor a cinco.

La vista `panel.blade.php` conservará el saludo y cierre de sesión, añadirá navegación mensual, tarjetas enlazadas a reportes filtrados y estados vacíos explícitos.

### Reportes

`ControladorReportes` expondrá dos rutas de solo lectura:

- `GET /reportes/cotizaciones`;
- `GET /reportes/ventas`.

Cotizaciones aceptará `desde`, `hasta`, `cliente`, `responsable` y `estado`. Mostrará fecha, folio, cliente, responsable, estado y total.

Ventas aceptará `desde`, `hasta`, `cliente`, `responsable` y `metodo_pago`. Mostrará fecha, folio, cotización de origen, cliente histórico, responsable histórico, método de pago y total.

Los filtros de identificadores deberán existir en sus tablas. Las fechas serán válidas y `hasta` no podrá ser anterior a `desde`. Si no se proporcionan fechas, ambos reportes usarán el mes actual. Los filtros permanecerán visibles en la URL para poder compartir o regresar a una consulta.

Los resultados se paginarán conservando los parámetros de filtro. Los totales del encabezado se calcularán sobre todo el conjunto filtrado antes de paginar, no solo sobre la página visible.

## Interfaz

La navegación añadirá Reportes para los tres roles. El panel tendrá:

- encabezado con mes seleccionado y controles para mes anterior, mes siguiente y selector de mes;
- tarjetas de total de cotizaciones y de cada estado;
- tarjetas de cantidad de ventas y total vendido;
- listas compactas de actividad reciente;
- bloque de inventario bajo.

Las tarjetas usarán texto además de color. Los enlaces y controles tendrán foco visible. Las tablas permanecerán dentro de contenedores con desplazamiento horizontal y encabezados asociados. En móvil, las tarjetas pasarán a una columna y los filtros se apilarán.

## Permisos y seguridad

Todas las rutas estarán detrás de autenticación y del middleware `role:admin,comercial,consulta`. Consulta tendrá acceso idéntico de lectura, sin formularios que escriban ni rutas nuevas de modificación. Eloquent y validación de Laravel manejarán consultas y parámetros; Blade escapará los valores mostrados.

## Errores y estados vacíos

- Un mes o fecha inválidos regresarán al formulario con mensajes en español y conservarán los filtros válidos.
- Un intervalo invertido mostrará un error de validación.
- Los meses sin operaciones mostrarán ceros y mensajes de ausencia, sin ocultar los controles.
- Las relaciones históricas de ventas usarán sus campos copiados para que cambios posteriores del cliente o responsable no alteren el reporte.

## Pruebas y aceptación

Las pruebas Feature verificarán:

- apertura del panel en el mes actual;
- navegación a otro mes;
- conteo de los seis estados según el estado actual de cotizaciones creadas en el periodo;
- porcentaje de aceptación y mes sin cotizaciones;
- cantidad y suma exacta de ventas por `sold_at`;
- límites de inicio y fin de periodo;
- filtros individuales y combinados de ambos reportes;
- totales sobre todos los resultados aunque exista paginación;
- acceso de Consulta y ausencia de operaciones de escritura;
- estados vacíos y mensajes de validación;
- navegación por teclado, contraste, adaptación móvil y ausencia de errores de consola mediante revisión en navegador.

La Entrega 6 se considerará terminada cuando los totales concilien con los registros originales, las pruebas completas pasen, las vistas estén verificadas en escritorio y móvil y exista un único commit de cierre en español.
