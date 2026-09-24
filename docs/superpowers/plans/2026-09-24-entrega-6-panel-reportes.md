# Entrega 6: panel y reportes — plan de implementación

> **Para agentes:** SUBHABILIDAD REQUERIDA: usar `superpowers:executing-plans` para ejecutar este plan tarea por tarea. Los pasos usan casillas de verificación.

**Objetivo:** Construir un panel mensual y reportes filtrables de cotizaciones y ventas cuyos totales concilien con las operaciones originales.

**Arquitectura:** `PeriodoReporte` centralizará los límites temporales. `ControladorPanel` consultará indicadores y actividad mensual; `ControladorReportes` construirá consultas filtradas, totales y paginación. Las vistas separarán resumen y detalle sin añadir rutas de escritura.

**Tecnología:** Laravel 12, PHP 8.3, Eloquent, Blade, CSS propio y pruebas Feature con SQLite.

**Especificación:** `docs/superpowers/specs/2026-09-24-entrega-6-panel-reportes-design.md`

## Restricciones globales

- El panel abre con el mes actual y acepta `mes=AAAA-MM`.
- Las cotizaciones se agrupan por `created_at` y se cuentan con su estado actual.
- Las ventas se agrupan por `sold_at` y suman el total histórico guardado.
- Administrador, Comercial y Consulta tienen lectura; no se añaden operaciones de escritura.
- Interfaz, mensajes, clases y pruebas propias se mantienen en español.
- No se añaden dependencias ni exportaciones PDF/Excel.
- Se conserva un único commit en español al cerrar toda la Entrega 6.

## Foco de revisión

- Mes inválido: debe mostrar validación en español sin ejecutar consultas ambiguas.
- Límites del periodo: incluir exactamente el inicio y excluir el primer instante del mes siguiente.
- Mes sin datos: mostrar ceros y evitar división entre cero.
- Filtros combinados: el total y la tabla deben usar exactamente la misma consulta.
- Paginación: debe conservar filtros y calcular totales sobre todos los resultados.

---

### Tarea 1: periodo e indicadores del panel

**Archivos:**

- Crear: `app/Support/PeriodoReporte.php`
- Crear: `app/Http/Controllers/ControladorPanel.php`
- Modificar: `routes/web.php`
- Modificar: `resources/views/panel.blade.php`
- Crear: `tests/Feature/PanelReportesTest.php`

**Interfaces:**

- Produce: `PeriodoReporte::desdeMes(?string $mes): self`, `inicio`, `finExclusivo`, `mes`, `anterior`, `siguiente`.
- Produce: `GET /panel?mes=AAAA-MM` con `resumenCotizaciones`, `cantidadVentas`, `totalVentas`, actividad reciente e inventario bajo.

- [x] **Paso 1: escribir pruebas fallidas del periodo mensual**

Crear cotizaciones en el mes anterior, al inicio del seleccionado y al inicio del siguiente. Crear ventas con `sold_at` en los mismos límites. Verificar que `/panel?mes=2026-08` solo cuenta las operaciones dentro de agosto, distribuye los seis estados actuales y suma ventas históricas.

```php
$respuesta = $this->actingAs($usuario)->get('/panel?mes=2026-08');

$respuesta->assertOk()
    ->assertViewHas('totalCotizaciones', 6)
    ->assertViewHas('cantidadVentas', 2)
    ->assertViewHas('totalVentas', 3500.00);
```

- [x] **Paso 2: ejecutar la prueba y confirmar RED**

Ejecutar: `php artisan test tests/Feature/PanelReportesTest.php --filter=panel`

Esperado: falla porque el panel actual no recibe mes ni expone indicadores.

- [x] **Paso 3: implementar el periodo y controlador**

`PeriodoReporte::desdeMes()` usará `CarbonImmutable::createFromFormat('!Y-m', $mes)` y rechazará valores cuyo formato reconstruido no coincida. El fin será `inicio->addMonth()` y todas las consultas usarán `>= inicio` y `< finExclusivo`.

`ControladorPanel::mostrar(Request $solicitud): View` validará `mes`, obtendrá conteos agrupados de `Cotizacion`, calculará porcentaje de aceptación, consultará ventas por `sold_at`, cargará cinco operaciones recientes y conservará la consulta existente de inventario bajo.

- [x] **Paso 4: implementar el panel mensual**

La vista mostrará selector de mes, enlaces anterior/siguiente, tarjetas enlazadas, actividad reciente y alertas. Todo valor monetario se mostrará con dos decimales.

- [x] **Paso 5: probar mes actual, mes vacío e inválido**

Agregar pruebas con `Carbon::setTestNow('2026-09-24 12:00:00')`, una base vacía y `mes=2026-13`. Verificar ceros, porcentaje `0.0` y mensaje de validación en español.

- [x] **Paso 6: ejecutar pruebas de la tarea**

Ejecutar: `php artisan test tests/Feature/PanelReportesTest.php`

Esperado: todas las pruebas del panel pasan.

---

### Tarea 2: reportes filtrables y paginados

**Archivos:**

- Crear: `app/Http/Controllers/ControladorReportes.php`
- Crear: `resources/views/reportes/cotizaciones.blade.php`
- Crear: `resources/views/reportes/ventas.blade.php`
- Modificar: `routes/web.php`
- Modificar: `tests/Feature/PanelReportesTest.php`

**Interfaces:**

- Consume: límites inclusivo/exclusivo de `PeriodoReporte`.
- Produce: `GET /reportes/cotizaciones` y `GET /reportes/ventas` con filtros persistentes, totales globales y paginación.

- [x] **Paso 1: escribir pruebas fallidas de cotizaciones**

Crear operaciones con clientes, responsables, estados y fechas distintas. Solicitar filtros combinados `desde`, `hasta`, `cliente`, `responsable`, `estado`; verificar que solo aparece el folio correcto y que total/cantidad corresponden al conjunto completo.

- [x] **Paso 2: confirmar RED de cotizaciones**

Ejecutar: `php artisan test tests/Feature/PanelReportesTest.php --filter=reporte_de_cotizaciones`

Esperado: falla con 404 porque la ruta no existe.

- [x] **Paso 3: implementar consulta de cotizaciones**

Validar fechas con `date_format:Y-m-d`, `hasta` con `after_or_equal:desde`, identificadores con `exists` y estado con `Rule::in`. Aplicar filtros condicionales, clonar la consulta para contar y sumar, cargar cliente/responsable y paginar 20 conservando la cadena de consulta.

- [x] **Paso 4: escribir pruebas fallidas de ventas**

Crear ventas con clientes, responsables, métodos y `sold_at` distintos. Filtrar por periodo, cliente, responsable y método. Verificar datos históricos y suma total. Crear 21 ventas y comprobar que el encabezado cuente y sume las 21 aunque la primera página muestre 20.

- [x] **Paso 5: confirmar RED de ventas**

Ejecutar: `php artisan test tests/Feature/PanelReportesTest.php --filter=reporte_de_ventas`

Esperado: falla con 404 porque la ruta no existe.

- [x] **Paso 6: implementar consulta de ventas**

Usar `sold_at`, `customer_id`, `user_id` y `payment_method`; mostrar campos históricos de cliente y responsable. Calcular cantidad y suma antes de paginar y preservar filtros con `withQueryString()`.

- [x] **Paso 7: validar errores y permisos**

Probar intervalo invertido, identificador inexistente y método inválido. Probar que Consulta recibe 200 en ambos reportes y que no aparece ningún formulario con método distinto de GET.

- [x] **Paso 8: ejecutar pruebas de la tarea**

Ejecutar: `php artisan test tests/Feature/PanelReportesTest.php`

Esperado: pasan indicadores, filtros, paginación, validación y permisos.

---

### Tarea 3: navegación, accesibilidad, revisión visual y cierre

**Archivos:**

- Modificar: `resources/views/componentes/navegacion.blade.php`
- Modificar: `public/css/administracion.css`
- Modificar: `tests/Feature/NavegacionTest.php`
- Modificar: `AGENTS.md`
- Modificar: `README.md`
- Modificar: `tasks/todo.md`

**Interfaces:**

- Consume: rutas `reportes.cotizaciones` y `reportes.ventas`.
- Produce: navegación Reportes y diseño adaptable con foco visible, tarjetas y filtros apilables.

- [x] **Paso 1: escribir prueba fallida de navegación**

Exigir que Administrador, Comercial y Consulta encuentren el enlace Reportes y que la sección activa use `aria-current="page"`.

- [x] **Paso 2: confirmar RED de navegación**

Ejecutar: `php artisan test tests/Feature/NavegacionTest.php`

Esperado: falla porque Reportes aún no está en el componente común.

- [x] **Paso 3: añadir navegación y estilos accesibles**

Agregar el enlace común. Crear cuadrícula adaptable de indicadores, filtros, listas recientes y estados vacíos. Mantener foco visible, etiquetas asociadas y tablas dentro de `.contenedor-tabla`.

- [x] **Paso 4: ejecutar verificaciones automatizadas**

Ejecutar:

```text
composer formato
php artisan test
php artisan view:cache
composer formato:verificar
git diff --check
```

Esperado: formato limpio, suite completa y Blade compilado.

- [x] **Paso 5: revisar en navegador**

Comprobar Panel, reporte de Cotizaciones y reporte de Ventas en escritorio y a 390 × 844. Verificar controles de mes, filtros, tablas, estados vacíos, foco por teclado, `scrollWidth <= innerWidth` y consola sin errores.

- [x] **Paso 6: actualizar documentación con evidencia real**

Registrar cifras exactas de pruebas y los límites de la revisión. No afirmar que Vite pasó si el sandbox bloquea `esbuild`.

- [x] **Paso 7: revisión integral del diff**

Revisar corrección, legibilidad, arquitectura, seguridad y rendimiento. Resolver hallazgos importantes con prueba RED→GREEN.

- [x] **Paso 8: crear el único commit final de la Entrega 6**

Usar un mensaje en español, por ejemplo `feat: completar entrega 6 de panel y reportes`, con cuerpo que enumere indicadores, filtros, permisos y validación.
