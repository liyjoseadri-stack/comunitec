# Inventario unificado y series en venta — Plan de implementación

> **Para agentes de implementación:** SUB-SKILL OBLIGATORIA: usar `superpowers:subagent-driven-development` (recomendada) o `superpowers:executing-plans` para ejecutar este plan tarea por tarea. Los pasos usan casillas (`- [ ]`) para seguimiento.

**Objetivo:** Unificar Catálogo e Inventario, administrar categorías y artículos desde un único módulo, reservar stock agregado y capturar exactamente las series requeridas al convertir una cotización aceptada en venta.

**Arquitectura:** Se conservarán `articulos_catalogo`, `categorias`, `partidas_cotizacion`, `ventas` y `partidas_venta`. El stock agregado en `articulos_catalogo.existencias` sustituirá las reservas por filas serializadas; `piezas_inventario` conservará y almacenará las series entregadas, vinculadas a partidas de venta. La transición cotización→venta seguirá siendo transaccional y cambiará el estado persistido a `venta`.

**Stack tecnológico:** Laravel 12, PHP 8.3, Eloquent, MySQL, SQLite para pruebas, Blade, CSS corporativo existente, PHPUnit/Pest mediante `php artisan test`.

**Especificación:** `docs/superpowers/specs/2026-09-25-inventario-unificado-series-venta-design.md`

## Restricciones globales

- Mantener en español nombres propios del dominio, mensajes, rutas nuevas y pruebas.
- No añadir carrito, checkout, pasarela, facturación electrónica, envíos, cobranza ni inventario avanzado.
- No perder ventas, cotizaciones, partidas ni series históricas.
- Conservar los permisos actuales: administrador y comercial escriben; consulta solo lee lo autorizado.
- Reutilizar el layout y componentes visuales existentes.
- Las series se capturan al registrar la venta, nunca al crear la cotización.
- Las cotizaciones históricas conservan su descripción, cantidad y precio.
- Cada tarea se desarrolla con ciclo prueba roja → implementación mínima → prueba verde → refactor → commit en español.

## Enfoque de revisión

- Una cotización con dos partidas del mismo producto debe reservar y devolver la suma correcta sin descuento doble; se prueba en la Tarea 2.
- Una solicitud manipulada con series para partidas ajenas, no serializables o cantidades adicionales debe rechazarse completa; se prueba en la Tarea 6.
- Una serie repetida con espacios laterales o distinta capitalización no debe crear duplicados; se prueba en la Tarea 6.
- Editar un producto o desactivar su categoría no debe alterar cotizaciones ni ventas históricas; se prueba en las Tareas 4 y 5.
- Una migración con cotizaciones ya vendidas y series entregadas debe conservar relaciones y establecer estado `venta`; se prueba en las Tareas 1 y 7.

---

### Tarea 1: Evolucionar el esquema y las relaciones del dominio

**Archivos:**
- Crear: `database/migrations/2026_09_25_000001_unificar_inventario_y_series_venta.php`
- Modificar: `app/Models/ArticuloCatalogo.php`
- Modificar: `app/Models/Categoria.php`
- Modificar: `app/Models/PiezaInventario.php`
- Modificar: `app/Models/PartidaCotizacion.php`
- Modificar: `app/Models/PartidaVenta.php`
- Prueba: `tests/Feature/EsquemaInventarioUnificadoTest.php`

**Interfaces:**
- Produce: `ArticuloCatalogo::requiere_numero_serie: bool`, `ArticuloCatalogo::descripcion: ?string`.
- Produce: `Categoria::articulos()`, `ArticuloCatalogo::partidasCotizacion()`, `ArticuloCatalogo::partidasVenta()` y `ArticuloCatalogo::seriesVendidas()`.
- Conserva: `PartidaVenta::piezas()` como relación con las series entregadas.

- [ ] **Paso 1: Escribir pruebas de esquema y relaciones**

Crear pruebas que exijan los nuevos campos, sus valores predeterminados y las relaciones:

```php
public function test_el_esquema_configura_series_por_producto(): void
{
    $this->assertTrue(Schema::hasColumns('articulos_catalogo', [
        'descripcion', 'requiere_numero_serie',
    ]));
}

public function test_categoria_producto_y_series_tienen_relaciones_navegables(): void
{
    $categoria = Categoria::create(['nombre' => 'Redes']);
    $producto = $categoria->articulos()->create([
        'tipo' => 'producto', 'nombre' => 'Switch', 'codigo' => 'SW-01',
        'unidad' => 'pieza', 'precio' => 1200, 'existencias' => 2,
        'requiere_numero_serie' => true,
    ]);

    $this->assertTrue($producto->categoria->is($categoria));
    $this->assertTrue($categoria->articulos->contains($producto));
}
```

- [ ] **Paso 2: Ejecutar la prueba y comprobar el fallo esperado**

Ejecutar: `php artisan test tests/Feature/EsquemaInventarioUnificadoTest.php`

Esperado: fallo porque faltan columnas y relaciones.

- [ ] **Paso 3: Crear migración compatible y relaciones Eloquent**

La migración añadirá:

```php
Schema::table('articulos_catalogo', function (Blueprint $tabla) {
    $tabla->text('descripcion')->nullable()->after('nombre');
    $tabla->boolean('requiere_numero_serie')->default(false)->after('existencias');
    $tabla->index(['tipo', 'activo']);
    $tabla->index(['categoria_id', 'activo']);
});
```

Actualizar `$fillable`, `casts()` y relaciones. Mantener la tabla `piezas_inventario`; no eliminar columnas heredadas hasta completar la transición y comprobar datos reales.

- [ ] **Paso 4: Probar migración hacia adelante y reversión en base aislada**

Ejecutar:

```powershell
php artisan migrate:fresh --env=testing
php artisan test tests/Feature/EsquemaInventarioUnificadoTest.php
```

Esperado: pruebas aprobadas.

- [ ] **Paso 5: Confirmar que las series entregadas existentes siguen relacionadas**

Añadir una prueba que cree venta, partida y `PiezaInventario` con `partida_venta_id`, y compruebe `producto->seriesVendidas` y `partida->piezas`.

- [ ] **Paso 6: Ejecutar pruebas relacionadas y confirmar verde**

Ejecutar: `php artisan test tests/Feature/EsquemaInventarioUnificadoTest.php tests/Feature/VentasTest.php`

- [ ] **Paso 7: Confirmar cambios de la tarea**

```powershell
git add database/migrations/2026_09_25_000001_unificar_inventario_y_series_venta.php app/Models tests/Feature/EsquemaInventarioUnificadoTest.php
git commit -m "feat: preparar esquema de inventario unificado"
```

### Tarea 2: Sustituir reservas por serie con reservas de stock agregado

**Archivos:**
- Modificar: `app/Services/ServicioInventarioCotizacion.php`
- Modificar: `app/Http/Controllers/ControladorCotizaciones.php`
- Modificar: `app/Http/Controllers/ControladorPartidas.php`
- Prueba: `tests/Feature/ReservasInventarioTest.php`
- Modificar pruebas: `tests/Feature/CotizacionesTest.php`
- Modificar pruebas: `tests/Feature/EdicionCotizacionesTest.php`

**Interfaces:**
- Produce: `ServicioInventarioCotizacion::reservar(Cotizacion $cotizacion): void` que descuenta stock agregado.
- Produce: `ServicioInventarioCotizacion::liberar(Cotizacion $cotizacion, bool $soloSiVencida = false): bool` que devuelve stock agregado una sola vez.
- Consume: artículos de partidas cargados por `articulo_catalogo_id`.

- [ ] **Paso 1: Escribir pruebas de reserva agrupada**

Cubrir dos partidas del mismo artículo, stock exacto, stock insuficiente sin cambios parciales y servicios ignorados:

```php
public function test_aceptar_reserva_la_suma_de_partidas_del_mismo_producto(): void
{
    // Producto con 5 unidades y dos partidas por 2 y 1.
    $this->post("/cotizaciones/{$cotizacion->id}/aceptar")->assertRedirect();
    $this->assertSame(2, $producto->fresh()->existencias);
    $this->assertSame('aceptada', $cotizacion->fresh()->estado);
}
```

- [ ] **Paso 2: Ejecutar pruebas y comprobar que fallan con la reserva por piezas**

Ejecutar: `php artisan test tests/Feature/ReservasInventarioTest.php`

- [ ] **Paso 3: Implementar bloqueo y descuento agregado**

En una transacción, agrupar partidas de producto por artículo, bloquear artículos con `lockForUpdate()`, validar existencias y decrementar solo después de validar todos:

```php
$cantidades = $cotizacion->partidas()
    ->where('tipo', 'producto')
    ->selectRaw('articulo_catalogo_id, SUM(cantidad) AS cantidad')
    ->groupBy('articulo_catalogo_id')
    ->get();
```

No consultar `piezas_inventario` para disponibilidad.

- [ ] **Paso 4: Centralizar la devolución de stock**

Hacer que edición de encabezado, edición/eliminación de partidas, cancelación y vencimiento llamen al servicio antes de cambiar desde `aceptada`. El servicio releerá y bloqueará la cotización para evitar devoluciones dobles.

- [ ] **Paso 5: Probar concurrencia lógica y devoluciones**

Añadir pruebas para:

- cancelar dos veces no suma stock dos veces;
- editar una aceptada devuelve las cantidades originales y pasa a pendiente;
- vencer devuelve cantidades;
- una copia obsoleta no devuelve stock de una cotización ya cambiada;
- dos partidas iguales se suman correctamente.

- [ ] **Paso 6: Ejecutar suites de cotización**

Ejecutar: `php artisan test tests/Feature/ReservasInventarioTest.php tests/Feature/CotizacionesTest.php tests/Feature/EdicionCotizacionesTest.php`

- [ ] **Paso 7: Confirmar cambios de la tarea**

```powershell
git add app/Services/ServicioInventarioCotizacion.php app/Http/Controllers/ControladorCotizaciones.php app/Http/Controllers/ControladorPartidas.php tests/Feature
git commit -m "refactor: reservar existencias por cantidad"
```

### Tarea 3: Crear administración completa de categorías

**Archivos:**
- Crear: `app/Http/Controllers/ControladorCategorias.php`
- Crear: `resources/views/inventario/categorias/listado.blade.php`
- Crear: `resources/views/inventario/categorias/detalle.blade.php`
- Modificar: `routes/web.php`
- Modificar: `public/css/administracion.css`
- Prueba: `tests/Feature/CategoriasTest.php`

**Interfaces:**
- Produce rutas nombradas `inventario.categorias.listado`, `.guardar`, `.detalle`, `.actualizar`, `.estado`.
- Consume: `Categoria::articulos()` de la Tarea 1.

- [ ] **Paso 1: Escribir pruebas de permisos y CRUD lógico**

Cubrir listado/búsqueda, alta, edición, detalle con productos, desactivación, reactivación, nombre único y rechazo a usuarios de consulta para escritura.

```php
$this->actingAs($comercial)->post('/inventario/categorias', [
    'nombre' => 'Videovigilancia',
])->assertRedirect();

$this->assertDatabaseHas('categorias', [
    'nombre' => 'Videovigilancia', 'activo' => true,
]);
```

- [ ] **Paso 2: Ejecutar la prueba y verificar 404/fallos por rutas inexistentes**

Ejecutar: `php artisan test tests/Feature/CategoriasTest.php`

- [ ] **Paso 3: Implementar controlador y rutas**

Validar `nombre` con `Rule::unique('categorias', 'nombre')->ignore($categoria)`. Aplicar las rutas de lectura al grupo autorizado y las mutaciones al grupo administrador/comercial.

- [ ] **Paso 4: Construir vistas con componentes existentes**

El listado tendrá buscador `buscar`, filtro `estado`, tabla responsiva, badges y botones. El detalle mostrará artículos relacionados sin formularios destructivos.

- [ ] **Paso 5: Ejecutar pruebas y revisar HTML accesible**

Ejecutar: `php artisan test tests/Feature/CategoriasTest.php tests/Feature/NavegacionTest.php`

- [ ] **Paso 6: Confirmar cambios de la tarea**

```powershell
git add app/Http/Controllers/ControladorCategorias.php resources/views/inventario/categorias routes/web.php public/css/administracion.css tests/Feature/CategoriasTest.php
git commit -m "feat: administrar categorias de inventario"
```

### Tarea 4: Unificar productos y servicios dentro de Inventario

**Archivos:**
- Crear: `app/Http/Controllers/ControladorProductos.php`
- Crear: `resources/views/inventario/productos/listado.blade.php`
- Crear: `resources/views/inventario/productos/formulario.blade.php`
- Crear: `resources/views/inventario/productos/detalle.blade.php`
- Modificar: `app/Http/Controllers/ControladorInventario.php`
- Modificar: `resources/views/inventario/listado.blade.php`
- Modificar: `resources/views/componentes/navegacion.blade.php`
- Modificar: `routes/web.php`
- Modificar: `public/css/administracion.css`
- Prueba: `tests/Feature/ProductosInventarioTest.php`

**Interfaces:**
- Produce rutas `inventario.productos.*` y mantiene `catalogo.listado` como redirección compatible.
- Produce filtros `buscar`, `categoria`, `tipo`, `estado`.

- [ ] **Paso 1: Escribir pruebas de alta, edición, detalle, filtros y estado**

Cubrir SKU único, categoría activa, descripción, precio, stock entero, `requiere_numero_serie`, servicio forzado a cero/sin series y conservación histórica:

```php
$this->actingAs($comercial)->post('/inventario/productos', [
    'tipo' => 'producto', 'nombre' => 'Memoria RAM', 'codigo' => 'RAM-16',
    'categoria_id' => $categoria->id, 'descripcion' => 'DDR4 16 GB',
    'unidad' => 'pieza', 'precio' => 850, 'existencias' => 5,
    'requiere_numero_serie' => '1',
])->assertRedirect();
```

- [ ] **Paso 2: Ejecutar la prueba y comprobar los fallos esperados**

Ejecutar: `php artisan test tests/Feature/ProductosInventarioTest.php`

- [ ] **Paso 3: Implementar validación reutilizable y consultas filtradas**

Crear métodos privados enfocados en `ControladorProductos` para reglas comunes. Usar `when()` y `whereHas('categoria')` para nombre, SKU y categoría. Paginar resultados para evitar cargar todo el inventario.

- [ ] **Paso 4: Implementar vistas del inventario unificado**

Reutilizar botones, tarjetas, insignias, tabla, mensajes y campos globales. Mostrar stock solo para productos y badge “Requiere series” cuando corresponda.

- [ ] **Paso 5: Retirar duplicidad visible de Catálogo**

Quitar “Catálogo” del Navbar, mantener solo “Inventario” y añadir acceso interno a Categorías. Redirigir rutas GET antiguas; no conservar formularios duplicados.

- [ ] **Paso 6: Ejecutar pruebas de catálogo, inventario, permisos y navegación**

Ejecutar: `php artisan test tests/Feature/ProductosInventarioTest.php tests/Feature/CatalogoTest.php tests/Feature/InventarioTest.php tests/Feature/AutorizacionRolesTest.php tests/Feature/NavegacionTest.php`

- [ ] **Paso 7: Confirmar cambios de la tarea**

```powershell
git add app/Http/Controllers resources/views/inventario resources/views/componentes/navegacion.blade.php routes/web.php public/css/administracion.css tests/Feature
git commit -m "feat: unificar productos y servicios en inventario"
```

### Tarea 5: Integrar búsqueda de inventario con cotizaciones sin perder históricos

**Archivos:**
- Modificar: `app/Http/Controllers/ControladorCotizaciones.php`
- Modificar: `app/Http/Controllers/ControladorPartidas.php`
- Modificar: `resources/views/cotizaciones/detalle.blade.php`
- Modificar: `public/css/administracion.css`
- Prueba: `tests/Feature/IntegracionInventarioCotizacionesTest.php`
- Modificar pruebas: `tests/Feature/CotizacionesTest.php`

**Interfaces:**
- Consume: artículos activos y filtros del inventario.
- Conserva: precio y descripción copiados en `PartidaCotizacion`.

- [ ] **Paso 1: Escribir pruebas del selector y snapshots históricos**

Comprobar búsqueda por nombre, SKU y categoría; rechazo de artículos inactivos; copia inicial de precio/descripción; permanencia después de editar el artículo:

```php
$partida = $cotizacion->partidas()->firstOrFail();
$producto->update(['nombre' => 'Nombre nuevo', 'precio' => 5500]);
$this->assertSame('5000.00', $partida->fresh()->precio_unitario);
$this->assertSame('Nombre cotizado', $partida->fresh()->descripcion);
```

- [ ] **Paso 2: Ejecutar pruebas y comprobar fallos del selector actual**

Ejecutar: `php artisan test tests/Feature/IntegracionInventarioCotizacionesTest.php`

- [ ] **Paso 3: Mejorar consulta y formulario de partidas**

Enviar artículos activos con categoría y atributos necesarios. Presentar opciones con `SKU · nombre · categoría` y permitir filtrado accesible en la vista sin cambiar el precio del servidor.

- [ ] **Paso 4: Mantener el servidor como autoridad del precio**

Conservar la regla actual: al elegir un artículo nuevo, tomar el precio desde base; al editar la misma partida histórica, conservar su precio cotizado.

- [ ] **Paso 5: Ejecutar suites de cotizaciones y PDF**

Ejecutar: `php artisan test tests/Feature/IntegracionInventarioCotizacionesTest.php tests/Feature/CotizacionesTest.php tests/Feature/EdicionCotizacionesTest.php`

- [ ] **Paso 6: Confirmar cambios de la tarea**

```powershell
git add app/Http/Controllers/ControladorCotizaciones.php app/Http/Controllers/ControladorPartidas.php resources/views/cotizaciones/detalle.blade.php public/css/administracion.css tests/Feature
git commit -m "feat: integrar inventario con cotizaciones"
```

### Tarea 6: Capturar series escritas al convertir una cotización en venta

**Archivos:**
- Modificar: `app/Http/Controllers/ControladorVentas.php`
- Modificar: `app/Services/ServicioConversionVenta.php`
- Modificar: `resources/views/cotizaciones/detalle.blade.php`
- Modificar: `resources/views/ventas/detalle.blade.php`
- Modificar: `public/css/administracion.css`
- Modificar pruebas: `tests/Feature/VentasTest.php`
- Crear prueba: `tests/Feature/SeriesVentaTest.php`

**Interfaces:**
- Cambia entrada de `series`: de identificadores enteros a cadenas agrupadas por `partida_cotizacion_id`.
- Produce: filas `PiezaInventario` con `numero_serie`, `articulo_catalogo_id`, `estado = entregada` y `partida_venta_id`.

- [ ] **Paso 1: Escribir pruebas del formulario generado en servidor**

Para producto serializable de cantidad 3, exigir exactamente tres inputs; para producto no serializable y servicio, ninguno:

```php
$respuesta->assertSee('name="series['.$partida->id.'][]"', false);
$this->assertSame(3, substr_count(
    $respuesta->getContent(),
    'name="series['.$partida->id.'][]"'
));
```

- [ ] **Paso 2: Escribir pruebas de validación transaccional**

Cubrir series faltantes, sobrantes, vacías, repetidas en solicitud, repetidas en base, claves de partida ajenas y series enviadas para producto no serializable. En cada fallo comprobar cero ventas, cero partidas nuevas y estado/stock sin cambios adicionales.

- [ ] **Paso 3: Ejecutar las pruebas y verificar el fallo con selectores antiguos**

Ejecutar: `php artisan test tests/Feature/SeriesVentaTest.php`

- [ ] **Paso 4: Cambiar validación HTTP a cadenas**

Validar estructura base en controlador y dejar la cardinalidad/propiedad a la capa transaccional:

```php
'series' => ['nullable', 'array'],
'series.*' => ['array'],
'series.*.*' => ['nullable', 'string', 'max:255'],
```

- [ ] **Paso 5: Validar y normalizar series dentro de la transacción**

Recortar espacios, rechazar vacíos, comparar sin diferencias accidentales de capitalización y consultar unicidad persistida. Determinar cardinalidad desde `PartidaCotizacion::cantidad` y `articulo->requiere_numero_serie`, nunca desde el arreglo recibido.

- [ ] **Paso 6: Crear venta, partidas y series atómicamente**

Por cada partida serializable:

```php
foreach ($seriesNormalizadas[$partidaCotizada->id] as $numeroSerie) {
    $partidaVendida->piezas()->create([
        'articulo_catalogo_id' => $partidaCotizada->articulo_catalogo_id,
        'numero_serie' => $numeroSerie,
        'estado' => 'entregada',
        'cotizacion_id' => null,
    ]);
}
```

- [ ] **Paso 7: Mostrar series por producto en la venta**

Usar lista o chips legibles, mantener “No aplica” para partidas sin series y exponer SKU/categoría cuando estén disponibles sin depender de datos actuales para descripción/precio.

- [ ] **Paso 8: Ejecutar pruebas de venta y rastreo**

Ejecutar: `php artisan test tests/Feature/SeriesVentaTest.php tests/Feature/VentasTest.php tests/Feature/InventarioTest.php`

- [ ] **Paso 9: Confirmar cambios de la tarea**

```powershell
git add app/Http/Controllers/ControladorVentas.php app/Services/ServicioConversionVenta.php resources/views/cotizaciones/detalle.blade.php resources/views/ventas/detalle.blade.php public/css/administracion.css tests/Feature
git commit -m "feat: capturar series al registrar ventas"
```

### Tarea 7: Convertir `venta` en estado terminal persistente

**Archivos:**
- Crear: `database/migrations/2026_09_25_000002_actualizar_estado_venta_cotizaciones.php`
- Modificar: `app/Models/Cotizacion.php`
- Modificar: `app/Services/ServicioConversionVenta.php`
- Modificar: `app/Http/Controllers/ControladorCotizaciones.php`
- Modificar: `app/Http/Controllers/ControladorReportes.php`
- Modificar: `resources/views/cotizaciones/listado.blade.php`
- Modificar: `resources/views/cotizaciones/detalle.blade.php`
- Modificar: `resources/views/cotizaciones/pdf.blade.php`
- Modificar: `resources/views/panel.blade.php`
- Modificar: `resources/views/reportes/cotizaciones.blade.php`
- Prueba: `tests/Feature/EstadoVentaTest.php`

**Interfaces:**
- Produce: `Cotizacion::ESTADO_VENTA = 'venta'` y etiqueta `Venta`.
- Consume: relación única `Cotizacion::venta()`.

- [ ] **Paso 1: Escribir pruebas de migración y transición**

Comprobar conversión de `se_hizo_venta` si existe, backfill de cotizaciones con venta, estado nuevo al convertir y bloqueo de todas las mutaciones posteriores.

- [ ] **Paso 2: Ejecutar las pruebas y comprobar que el estado sigue aceptado**

Ejecutar: `php artisan test tests/Feature/EstadoVentaTest.php`

- [ ] **Paso 3: Crear migración idempotente de datos**

Actualizar primero `se_hizo_venta` a `venta` y después toda cotización relacionada con `ventas`. El `down()` solo podrá restaurar `aceptada` para filas con venta, documentando la limitación semántica.

- [ ] **Paso 4: Centralizar constantes y reglas de estado**

Añadir constantes de estados en `Cotizacion`, usar `ESTADO_VENTA` en servicios/controladores y eliminar cadenas inconsistentes en consultas y vistas.

- [ ] **Paso 5: Actualizar reportes, badges y PDF**

Los conteos mensuales separarán `aceptada` y `venta`; filtros aceptarán `venta`; la etiqueta será exactamente “Venta”. El PDF histórico mostrará el estado correcto sin incluir series.

- [ ] **Paso 6: Buscar vocabulario antiguo**

Ejecutar:

```powershell
rg -n -i "se[_ ]hizo[_ ]venta|convertida en venta|venta cerrada" app database routes resources tests
```

Revisar cada resultado y conservar únicamente frases descriptivas que no representen el estado.

- [ ] **Paso 7: Ejecutar pruebas de estados, reportes y PDF**

Ejecutar: `php artisan test tests/Feature/EstadoVentaTest.php tests/Feature/PanelReportesTest.php tests/Feature/CotizacionesTest.php tests/Feature/VentasTest.php`

- [ ] **Paso 8: Confirmar cambios de la tarea**

```powershell
git add database/migrations/2026_09_25_000002_actualizar_estado_venta_cotizaciones.php app resources/views tests/Feature
git commit -m "feat: establecer venta como estado final"
```

### Tarea 8: Retirar flujo anterior, completar navegación y revisar compatibilidad

**Archivos:**
- Modificar o eliminar si queda sin uso: `app/Http/Controllers/ControladorCatalogo.php`
- Modificar: `app/Http/Controllers/ControladorInventario.php`
- Modificar: `resources/views/componentes/navegacion.blade.php`
- Modificar o eliminar si queda sin uso: `resources/views/catalogo/listado.blade.php`
- Modificar: `routes/web.php`
- Modificar: `AGENTS.md`
- Modificar pruebas: `tests/Feature/NavegacionTest.php`
- Modificar pruebas: `tests/Feature/AutorizacionRolesTest.php`

**Interfaces:**
- Produce: navegación única hacia Inventario y Categorías, con estado activo correcto.
- Conserva: redirección compatible desde `/catalogo`.

- [ ] **Paso 1: Escribir pruebas de navegación y compatibilidad**

Comprobar que el Navbar no duplica Catálogo/Inventario, que administrador y comercial ven Inventario, que consulta no obtiene formularios de escritura y que `/catalogo` redirige al listado nuevo.

- [ ] **Paso 2: Ejecutar pruebas y verificar el fallo por navegación duplicada**

Ejecutar: `php artisan test tests/Feature/NavegacionTest.php tests/Feature/AutorizacionRolesTest.php`

- [ ] **Paso 3: Retirar controladores, vistas y rutas sin consumidores**

Usar `rg` para demostrar que no hay referencias antes de eliminar cada archivo. Mantener una ruta de redirección nombrada si pruebas o marcadores antiguos dependen de ella.

- [ ] **Paso 4: Actualizar documentación operativa**

Registrar en `AGENTS.md` el nuevo flujo, estado `venta`, stock agregado, captura de series y comandos de verificación. No escribir credenciales ni datos privados.

- [ ] **Paso 5: Ejecutar suites de permisos y navegación**

Ejecutar: `php artisan test tests/Feature/NavegacionTest.php tests/Feature/AutorizacionRolesTest.php tests/Feature/PermisosCotizacionesTest.php`

- [ ] **Paso 6: Confirmar cambios de la tarea**

```powershell
git add app/Http/Controllers resources/views routes/web.php AGENTS.md tests/Feature
git commit -m "refactor: consolidar navegacion de inventario"
```

### Tarea 9: Verificación integral, migración MySQL y revisión visual

**Archivos:**
- Modificar si aparecen defectos: archivos de las Tareas 1–8.
- Actualizar: `.superpowers/sdd/2026-09-25-inventario-unificado-series-venta/progress.md` (registro local ignorado).

**Interfaces:**
- Consume todo el flujo implementado.
- Produce evidencia reproducible de entrega.

- [ ] **Paso 1: Ejecutar formato y pruebas completas**

```powershell
composer formato
php artisan test
php artisan view:cache
composer formato:verificar
git diff --check
```

Esperado: todos los comandos terminan con código cero.

- [ ] **Paso 2: Verificar rutas y migraciones**

```powershell
php artisan route:list
php artisan migrate:status
```

Revisar nombres, middleware, métodos HTTP y estado de migraciones nuevas.

- [ ] **Paso 3: Aplicar migraciones en MySQL local con respaldo previo**

Confirmar que la conexión apunta a la base de desarrollo del proyecto. Crear respaldo antes de `php artisan migrate`. Si el usuario decide reiniciar la base porque no hay datos importantes, usar `php artisan migrate:fresh --seed` únicamente después de confirmar que esa conexión no contiene datos que deban conservarse.

- [ ] **Paso 4: Compilar frontend**

Ejecutar: `npm run build`

Esperado: Vite termina sin errores. Si el sandbox bloquea esbuild, registrar la limitación y pedir la ejecución en la terminal normal del usuario.

- [ ] **Paso 5: Probar recorridos reales en navegador**

Con usuarios de administrador, comercial y consulta revisar:

1. crear/editar/desactivar categoría;
2. crear/editar/desactivar producto serializable con stock 5;
3. crear cotización por varias partidas;
4. enviar y aceptar, comprobando reserva;
5. registrar venta y capturar exactamente las series;
6. consultar series en venta e inventario;
7. cambiar precio/nombre del producto y volver a revisar el histórico;
8. buscar y filtrar en móvil, tableta y escritorio;
9. verificar consola del navegador sin errores.

- [ ] **Paso 6: Revisar seguridad, rendimiento y regresiones**

Comprobar CSRF, autorización directa por ruta, ausencia de asignación masiva insegura, consultas N+1 en listados y límites de paginación. Ejecutar `composer audit` y `npm audit --omit=dev` si el entorno permite red.

- [ ] **Paso 7: Realizar revisión final del diff**

Revisar corrección, legibilidad, arquitectura, seguridad y rendimiento. Corregir hallazgos críticos o importantes y repetir las pruebas afectadas.

- [ ] **Paso 8: Confirmar entrega completa**

```powershell
git add -- AGENTS.md app database/migrations public/css/administracion.css resources/views routes/web.php tests/Feature
git commit -m "feat: completar inventario unificado y series en ventas"
```

No incluir `output/` ni los archivos temporales `work-entry*.php`.
