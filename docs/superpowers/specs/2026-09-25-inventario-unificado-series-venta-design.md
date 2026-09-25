# Inventario unificado y números de serie al registrar la venta

## Objetivo

Unificar las funciones actuales de Catálogo e Inventario en un solo módulo administrativo llamado **Inventario**. Este módulo administrará categorías, productos, servicios, existencias y la configuración de números de serie. Las series se capturarán cuando una cotización aceptada se registre como venta, nunca durante la creación de la cotización ni como requisito previo para dar de alta existencias.

La aplicación seguirá siendo un sistema administrativo. Este cambio no introduce carrito, checkout, cobros, facturación electrónica, envíos ni pasarelas de pago.

## Situación actual comprobada

- `categorias` y `articulos_catalogo` ya representan categorías, productos y servicios.
- `articulos_catalogo.existencias` guarda una cantidad numérica, pero el flujo de aceptación consulta realmente filas de `piezas_inventario`.
- `piezas_inventario` exige registrar una serie antes de aceptar una cotización y reserva piezas específicas.
- Las cotizaciones conservan descripción, cantidad, precio unitario y subtotal en `partidas_cotizacion`, por lo que sus datos históricos ya no dependen del precio actual del catálogo.
- Las ventas copian nuevamente sus partidas a `partidas_venta` y conservan datos históricos del cliente y responsable.
- Una venta se detecta por la relación `cotizacion->venta`; la cotización permanece con estado `aceptada` después de convertirse.
- Categorías se crean desde Inventario, mientras los artículos se crean y desactivan desde Catálogo. No existen edición, detalle, búsqueda o filtros completos para estos datos maestros.

## Decisiones de arquitectura

### Un solo módulo de Inventario

El Navbar mostrará una sola opción **Inventario** para administradores y comerciales. El módulo contendrá dos áreas relacionadas:

1. **Productos y servicios**: listado, búsqueda, filtros, altas, consulta, edición y activación/desactivación.
2. **Categorías**: listado, búsqueda, altas, edición, activación/desactivación y consulta de artículos asociados.

Las rutas anteriores de Catálogo dejarán de ser la interfaz principal. Se conservarán redirecciones compatibles durante la transición para evitar enlaces rotos.

### Existencias agregadas

`articulos_catalogo.existencias` será la fuente de verdad del stock disponible de un producto. Los servicios tendrán siempre cero existencias y no participarán en reservas.

Al aceptar una cotización, el sistema bloqueará los artículos afectados dentro de una transacción, comprobará el stock agregado y descontará las cantidades reservadas. Al editar, cancelar o vencer una cotización aceptada, devolverá exactamente esas cantidades. Convertirla en venta no volverá a descontar stock.

No se añadirá un inventario de movimientos complejo. La cotización aceptada será el registro que justifica temporalmente la reserva durante cinco días.

### Números de serie al cerrar la venta

Se añadirá `requiere_numero_serie` a `articulos_catalogo`. Será `false` por defecto para conservar compatibilidad. Los servicios siempre lo tendrán desactivado.

Cuando una cotización aceptada se vaya a convertir en venta, el formulario mostrará, por cada partida de producto serializable, exactamente un campo por unidad entera. Los nombres de los campos estarán agrupados por el identificador de la partida cotizada.

La conversión validará dentro de la misma transacción:

- que la cotización siga aceptada, vigente y sin venta;
- que cada partida serializable reciba exactamente tantas series como unidades;
- que no existan valores vacíos;
- que una serie no se repita dentro de la solicitud;
- que una serie no exista ya en el historial persistido;
- que no se reciban series para partidas ajenas a la cotización;
- que servicios y productos no serializables no requieran series.

Cada serie quedará en una fila individual asociada con `partida_venta_id`. La relación con producto y venta se obtiene de la partida vendida; también se conservará `articulo_catalogo_id` para consultas directas y compatibilidad con el historial actual.

### Reutilización de `piezas_inventario`

Se reutilizará la tabla existente en lugar de introducir una segunda tabla que represente el mismo concepto. Su significado final será “números de serie entregados en ventas”.

La migración será conservadora:

- añadirá `requiere_numero_serie` y `descripcion` a los artículos;
- mantendrá las series ya entregadas y sus relaciones con partidas de venta;
- conservará temporalmente columnas heredadas necesarias para una migración segura si la base contiene filas disponibles o reservadas;
- transformará o archivará mediante una migración explícita las filas no entregadas antes de retirar restricciones antiguas;
- no borrará ventas, cotizaciones, partidas ni series históricas;
- añadirá los índices y restricciones necesarios para unicidad y consultas por venta/producto.

La implementación comprobará el motor real MySQL y la suite SQLite antes de elegir operaciones de renombrado o eliminación de columnas. Si una eliminación segura exige dos pasos, se conservarán columnas heredadas sin uso durante esta entrega y se documentará su retiro posterior.

## Estado `venta`

`venta` será un estado real de `cotizaciones`, no solo una etiqueta visual.

- Se añadirá a `Cotizacion::estados()` y `etiquetaEstado()`.
- La conversión actualizará la cotización a `venta`, limpiará su vencimiento y creará la venta en una sola transacción.
- Una migración actualizará a `venta` las cotizaciones que ya tengan una fila relacionada en `ventas`.
- Todas las consultas, filtros, reportes, badges, reglas de edición, PDF y pruebas usarán el mismo valor.
- `venta` será terminal: no permitirá editar partidas, modificar encabezado, aceptar, rechazar, cancelar ni crear una segunda venta.

No se usará `se_hizo_venta` como valor nuevo. La migración también convertirá ese valor si aparece en alguna base existente.

## Modelo de datos final

### `categorias`

- `id`
- `nombre`, único
- `activo`
- marcas de tiempo
- relación `tieneMuchos(ArticuloCatalogo)`

Una categoría con artículos históricos no se eliminará físicamente. Se desactivará. Una categoría desactivada seguirá visible en registros históricos y no estará disponible para artículos nuevos, salvo que se reactive.

### `articulos_catalogo`

- conserva tipo, nombre, código/SKU, categoría, marca, modelo, unidad, precio, existencias y estado;
- agrega `descripcion`, nullable para compatibilidad;
- agrega `requiere_numero_serie`, booleano con valor inicial `false`;
- pertenece a una categoría activa para nuevas altas y cambios;
- tiene partidas cotizadas, partidas vendidas y series entregadas.

Los artículos con historial no se eliminan físicamente. Un producto puede desactivarse. Los servicios fuerzan `existencias = 0` y `requiere_numero_serie = false`.

### `partidas_cotizacion`

Conserva su referencia nullable al artículo y sus copias históricas de tipo, descripción, cantidad y precio. La selección inicial tomará nombre/descripción y precio del inventario, pero los cambios posteriores del artículo no modificarán la partida.

### `partidas_venta`

Conserva la copia histórica procedente de la cotización. Tendrá muchas filas en `piezas_inventario` cuando el producto requiera series.

### `piezas_inventario`

Cada fila representa una serie entregada:

- serie única;
- producto asociado;
- partida de venta asociada;
- acceso indirecto a venta, cotización y cliente;
- marcas de tiempo.

## Rutas y controladores

El módulo se dividirá por responsabilidad, reutilizando el sistema visual actual:

- `ControladorInventario`: tablero/listado y existencias.
- `ControladorProductos`: crear, mostrar, editar y cambiar estado de productos y servicios.
- `ControladorCategorias`: listar, crear, mostrar, editar y cambiar estado.
- `ControladorVentas` y `ServicioConversionVenta`: captura y persistencia transaccional de series.
- `ServicioInventarioCotizacion`: reserva agregada y devolución de stock.

Rutas previstas:

- `GET /inventario`
- `GET|POST /inventario/productos`
- `GET|PUT /inventario/productos/{articulo}`
- `PATCH /inventario/productos/{articulo}/estado`
- `GET|POST /inventario/categorias`
- `GET|PUT /inventario/categorias/{categoria}`
- `PATCH /inventario/categorias/{categoria}/estado`

Las rutas de lectura respetarán los permisos existentes. Administrador y comercial podrán escribir; consulta solo accederá a las vistas autorizadas que se definan en la matriz existente. No se relajará middleware ni autorización de servidor.

## Interfaz

Se reutilizarán `layouts.aplicacion`, `x-boton`, `x-insignia-estado`, `x-encabezado-pagina`, las tablas responsivas y los estilos corporativos.

Inventario ofrecerá:

- resumen de productos activos, inactivos, servicios y stock bajo;
- buscador por nombre, código/SKU, marca o modelo;
- filtros por tipo, categoría y estado;
- botones uniformes para crear, ver, editar y cambiar estado;
- formularios separados y claros para productos y categorías;
- detalle de categoría con sus artículos;
- detalle de producto con sus datos y series vendidas, cuando existan.

El formulario final de una cotización aceptada mostrará método de pago y, debajo, los campos de serie agrupados por producto. La cantidad de campos será generada en servidor a partir de la partida persistida, no desde valores enviados por el navegador.

La vista de venta mostrará cada serie individualmente y permitirá reconocer el producto correspondiente. El PDF de cotización seguirá representando una propuesta y mostrará SKU, sin series. Las series se mostrarán en el registro de venta; si se requiere un PDF de venta en el futuro será una capacidad separada.

## Validaciones e integridad

- Código/SKU único, normalizado y obligatorio.
- Nombre, tipo, unidad y precio obligatorios; precio no negativo.
- Categoría existente y activa para altas o reasignaciones.
- Stock entero no negativo para productos.
- Cantidades enteras para productos en cotizaciones.
- Series recortadas, no vacías y únicas sin distinguir espacios accidentales.
- Número exacto de series por partida serializable.
- Bloqueos `FOR UPDATE` y transacciones para reservas, liberaciones y ventas.
- Restricción de una venta por cotización.
- Bloqueo de eliminación física cuando exista historial; se usa desactivación.
- Mensajes de validación en español y conservación de datos anteriores en formularios.

## Compatibilidad y migración

La entrega seguirá una estrategia de expansión y transición:

1. añadir campos y relaciones compatibles;
2. migrar estados y series históricas;
3. cambiar servicios y controladores al stock agregado;
4. actualizar interfaz, rutas y reportes;
5. comprobar que ya no exista código que dependa de reservas por serie;
6. retirar o dejar explícitamente obsoletas las columnas heredadas según lo permita una migración reversible y segura.

No se reconstruirá la base desde cero ni se modificará la migración consolidada que ya pudo ejecutarse en instalaciones existentes. Se crearán migraciones nuevas.

## Pruebas y criterios de aceptación

La implementación se hará con pruebas antes del cambio de comportamiento. Como mínimo se comprobará:

- creación, edición, búsqueda, filtros y activación de categorías;
- categoría con historial no eliminada físicamente;
- creación, edición, detalle, búsqueda, filtros y estado de productos/servicios;
- servicios sin stock ni series;
- reserva agregada suficiente e insuficiente;
- devolución exacta de stock al editar, cancelar o vencer;
- conversión sin descuento doble;
- estado final `venta` y migración de ventas existentes;
- generación exacta de campos de serie;
- rechazo de series faltantes, sobrantes, duplicadas o asociadas a otra partida;
- producto no serializable convertido sin series;
- consulta posterior de series por venta y producto;
- permanencia del precio y descripción históricos tras editar el inventario;
- permisos para administrador, comercial y consulta;
- PDF de cotización, autenticación, clientes, usuarios y reportes sin regresiones;
- migraciones válidas tanto en MySQL como en la base aislada de pruebas;
- vistas Blade compilables, formato, suite PHP, compilación frontend y revisión responsive.

## Fuera de alcance

- carrito y checkout;
- pagos reales o pasarela;
- facturación electrónica;
- envíos y logística;
- proveedores, compras y órdenes de abastecimiento;
- kardex o inventario de movimientos avanzado;
- lotes, almacenes múltiples o ubicaciones;
- PDF específico de venta, salvo que se solicite posteriormente.

## Resultado esperado

El usuario administra todos los datos comerciales desde Inventario, crea cotizaciones con artículos localizables por nombre, SKU o categoría, acepta la propuesta reservando cantidades y registra la venta escribiendo únicamente las series requeridas. La cotización termina coherentemente en estado `venta`, y cada serie queda trazable hasta su producto, venta, cotización y cliente sin alterar el historial económico de operaciones anteriores.
