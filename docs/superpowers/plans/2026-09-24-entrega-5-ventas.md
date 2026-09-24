# Entrega 5: ventas e inventario

> **Para agentes:** usar `superpowers:executing-plans` para implementar este plan tarea por tarea.

**Objetivo:** Convertir una cotización aceptada en una única venta, copiar sus datos históricos, registrar el método de pago y ligar las series entregadas sin descontar inventario por segunda vez.

**Arquitectura:** La conversión se ejecutará en un servicio transaccional que bloqueará la cotización y las piezas reservadas. Una venta conservará copias de cliente, responsable, importes y partidas; las piezas pasarán de reservadas a entregadas y quedarán ligadas a su partida de venta. La existencia de una venta, protegida por una restricción única sobre la cotización, impedirá reintentos duplicados.

**Tecnología:** Laravel 12, PHP 8.3, Eloquent, MySQL y pruebas Feature con SQLite.

**Especificación:** `tasks/plan.md`, sección “Entrega 5: aceptación, reserva de inventario y venta”.

## Restricciones globales

- Toda venta proviene de una cotización aceptada; no existen ventas directas.
- La conversión es manual y no procesa pagos.
- Métodos: efectivo, transferencia, tarjeta u otro; “otro” requiere descripción.
- Las piezas ya fueron reservadas al aceptar y no deben descontarse nuevamente.
- Cada producto físico requiere una serie por pieza.
- Servicios y conceptos libres no usan inventario.
- Código, interfaz, mensajes y pruebas propios se escriben en español.
- Las operaciones de escritura corresponden a Administrador y Comercial; Consulta solo puede leer.

## Riesgos que deben cubrir las pruebas

- Dos solicitudes sobre la misma cotización no pueden crear dos ventas.
- Una serie ajena, disponible o de otro producto no puede entregarse.
- Las series repetidas o incompletas deben revertir toda la conversión.
- Una cotización vencida, cancelada o no aceptada no puede convertirse.
- Un fallo durante la copia no puede dejar venta, partidas o piezas parcialmente actualizadas.

---

### Tarea 1: Persistencia histórica de ventas

**Archivos:**

- Crear: `database/migrations/2026_09_24_000008_create_sales_tables.php`
- Crear: `database/migrations/2026_09_24_000009_add_customer_snapshot_to_sales_table.php`
- Crear: `database/migrations/2026_09_24_000010_add_responsible_snapshot_to_sales_table.php`
- Crear: `app/Models/Venta.php`
- Crear: `app/Models/PartidaVenta.php`
- Modificar: `app/Models/Cotizacion.php`
- Modificar: `app/Models/PiezaInventario.php`
- Probar: `tests/Feature/VentasTest.php`

**Contrato producido:** `Cotizacion::venta()`, `Venta::partidas()` y `PartidaVenta::piezas()`; `sales.quote_id` será único.

- [x] Escribir una prueba que describa una venta vinculada a una cotización, partidas históricas y una pieza entregada.
- [x] Ejecutarla y comprobar que falla porque las tablas y modelos no existen.
- [x] Crear las tablas `sales`, `sale_lines` y la relación `inventory_units.sale_line_id`.
- [x] Crear los modelos y relaciones mínimas.
- [x] Ejecutar la prueba y la suite existente.

### Tarea 2: Conversión transaccional e idempotente

**Archivos:**

- Crear: `app/Services/ServicioConversionVenta.php`
- Crear: `app/Http/Controllers/ControladorVentas.php`
- Modificar: `routes/web.php`
- Probar: `tests/Feature/VentasTest.php`

**Contrato producido:** `ServicioConversionVenta::convertir(Cotizacion, Usuario, string, ?string, array): Venta` y `POST /cotizaciones/{quote}/venta`.

- [x] Probar primero la conversión correcta con producto, servicio y concepto libre.
- [x] Comprobar el fallo inicial por ausencia del servicio/ruta.
- [x] Implementar validación del método de pago y la conversión dentro de una transacción.
- [x] Copiar partidas e importes; marcar solo las series reservadas seleccionadas como entregadas.
- [x] Probar rechazo de series incompletas, repetidas, ajenas o de otro producto.
- [x] Probar conversión duplicada y estados no elegibles.
- [x] Ejecutar la suite completa.

### Tarea 3: Pantallas, permisos y cierre operativo

**Archivos:**

- Crear: `resources/views/ventas/listado.blade.php`
- Crear: `resources/views/ventas/detalle.blade.php`
- Modificar: `resources/views/cotizaciones/detalle.blade.php`
- Modificar: `resources/views/componentes/navegacion.blade.php`
- Modificar: `app/Http/Controllers/ControladorCotizaciones.php`
- Modificar: `tests/Feature/VentasTest.php`
- Modificar: `tests/Feature/PermisosCotizacionesTest.php`

**Contrato producido:** formulario de conversión con un selector por pieza y vistas de ventas de solo lectura para los tres roles.

- [x] Probar que una aceptada muestra método de pago y series reservadas.
- [x] Probar que Consulta puede listar y ver ventas, pero recibe 403 al convertir.
- [x] Implementar el formulario, listado, detalle y navegación en español.
- [x] Ocultar y bloquear cambios a una cotización que ya tiene venta.
- [x] Revisar las vistas en móvil y escritorio.
- [x] Ejecutar Pint, Blade, pruebas completas y migración MySQL.
- [x] Actualizar `AGENTS.md`, `README.md` y `tasks/todo.md` con evidencia real.
- [x] Crear el commit final de la Entrega 5 en español.
