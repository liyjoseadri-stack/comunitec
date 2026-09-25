# Renovación visual integral de COMUN&TEC — Plan de implementación

> **Para agentes de implementación:** HABILIDAD REQUERIDA: usar `superpowers:subagent-driven-development` (recomendado) o `superpowers:executing-plans` para ejecutar este plan tarea por tarea. Los pasos usan casillas (`- [ ]`) para dar seguimiento.

**Objetivo:** Unificar la interfaz web administrativa de COMUN&TEC con un sistema visual corporativo, responsive y accesible, sin cambiar su comportamiento.

**Arquitectura:** Un layout Blade autenticado concentrará la estructura común y cargará una hoja de estilos global basada en tokens. Componentes Blade pequeños representarán botones, badges, alertas y encabezados; las vistas de cada módulo conservarán sus formularios, rutas y condiciones, pero usarán estos patrones comunes.

**Tecnologías:** Laravel 12, Blade, CSS moderno, Vite 7, PHPUnit, navegador integrado.

**Especificación:** `docs/superpowers/specs/2026-09-24-renovacion-visual-integral-design.md`

## Restricciones globales

- Mantener controladores, modelos, migraciones, base de datos, rutas, validaciones y reglas de negocio sin cambios.
- Conservar nombres, métodos, destinos, tokens CSRF, permisos y condiciones de todos los formularios.
- Usar exclusivamente la gama corporativa verde azulado, neutros fríos y colores semánticos para éxito, advertencia y peligro.
- Mantener el PDF de cotización y la plantilla de correo fuera del layout web.
- Mantener todo el texto propio del proyecto en español.
- Usar controles interactivos de al menos 44 píxeles cuando corresponda y foco visible.
- Verificar 320, 768, 1024 y 1440 píxeles.
- No agregar dependencias frontend.

## Enfoque de revisión

- Navegación con muchos módulos en 320 px: debe envolver sin desbordar el documento y conservar todos los enlaces autorizados.
- Grupos con acciones de distinta longitud: deben mantener altura y separación uniformes sin alterar el formulario que envían.
- Tablas con contenido largo: deben desplazarse dentro de su contenedor sin ampliar el ancho de la página.
- Formularios con errores y valores anteriores: deben conservar etiquetas, mensajes, campos y datos enviados.
- Roles con permisos diferentes: el layout debe conservar exactamente la visibilidad actual de cada opción y acción.

---

### Tarea 1: Fundamentos del sistema visual

**Archivos:**
- Crear: `resources/views/layouts/aplicacion.blade.php`
- Crear: `resources/views/componentes/boton.blade.php`
- Crear: `resources/views/componentes/insignia-estado.blade.php`
- Crear: `resources/views/componentes/encabezado-pagina.blade.php`
- Modificar: `resources/views/componentes/errores-validacion.blade.php`
- Modificar: `resources/views/componentes/paginacion.blade.php`
- Modificar: `public/css/administracion.css`
- Modificar: `public/css/navegacion.css`
- Modificar: `tests/Feature/NavegacionTest.php`

**Interfaces:**
- Produce: layout `<x-layouts.aplicacion :titulo="$titulo">`, botón `<x-boton variante="..." href="...">`, badge `<x-insignia-estado :estado="$estado">` y encabezado `<x-encabezado-pagina titulo="...">`.
- Consume: `componentes.navegacion`, sesión autenticada y rutas existentes.

- [ ] **Paso 1: ampliar la prueba de navegación compartida**

Agregar a `NavegacionTest` una prueba que recorra las páginas principales y compruebe las marcas del layout:

```php
public function test_las_paginas_administrativas_comparten_el_layout_visual(): void
{
    $usuario = Usuario::factory()->create(['rol' => Usuario::ROL_ADMINISTRADOR]);

    foreach (['/panel', '/clientes', '/catalogo', '/inventario', '/cotizaciones', '/ventas'] as $ruta) {
        $this->actingAs($usuario)->get($ruta)
            ->assertOk()
            ->assertSee('class="aplicacion"', false)
            ->assertSee('css/administracion.css', false);
    }
}
```

- [ ] **Paso 2: ejecutar la prueba y confirmar que falla**

Ejecutar: `php artisan test tests/Feature/NavegacionTest.php --stop-on-failure`

Resultado esperado: falla porque las vistas aún no usan `class="aplicacion"` de forma compartida.

- [ ] **Paso 3: crear layout y componentes base**

El layout debe contener el documento HTML, estilos, navegación, mensajes de sesión y los slots `encabezado` y principal. El botón debe mapear únicamente estas variantes:

```php
@props(['variante' => 'principal', 'href' => null, 'tipo' => 'button'])
@php($clases = 'boton boton--'.$variante)
@if ($href)
    <a {{ $attributes->class($clases)->merge(['href' => $href]) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->class($clases)->merge(['type' => $tipo]) }}>{{ $slot }}</button>
@endif
```

La insignia debe conservar el texto de estado y producir clases normalizadas, sin alterar valores de dominio.

- [ ] **Paso 4: consolidar los tokens y patrones CSS**

Definir en `:root` los tokens corporativos y construir las clases globales:

```css
:root {
    --color-marca-700: #0f6f70;
    --color-marca-800: #07595b;
    --color-marca-50: #edfafa;
    --color-texto: #17202a;
    --color-texto-suave: #52606d;
    --color-fondo: #f4f7f8;
    --color-superficie: #ffffff;
    --color-borde: #d8e1e5;
    --radio-control: 0.625rem;
    --sombra-tarjeta: 0 10px 30px rgb(15 111 112 / 8%);
    --espacio-1: 0.25rem;
    --espacio-2: 0.5rem;
    --espacio-3: 0.75rem;
    --espacio-4: 1rem;
    --espacio-6: 1.5rem;
}
```

Crear reglas reutilizables para `.boton`, variantes, `.grupo-acciones`, `.insignia`, `.alerta`, `.campo-formulario`, `.bloque-administrativo`, `.tabla-registros`, `.tarjeta-indicador` y breakpoints.

- [ ] **Paso 5: migrar temporalmente las vistas mínimas requeridas por la prueba al layout**

Cambiar cada vista administrativa para comenzar con:

```blade
<x-layouts.aplicacion titulo="Título del módulo">
    {{-- contenido existente, sin cambiar formularios ni condiciones --}}
</x-layouts.aplicacion>
```

En esta tarea solo retirar el HTML exterior duplicado y conservar el contenido interior sin rediseñarlo todavía.

- [ ] **Paso 6: verificar los fundamentos**

Ejecutar:

```bash
php artisan test tests/Feature/NavegacionTest.php --stop-on-failure
php artisan view:cache
composer formato:verificar
git diff --check
```

- [ ] **Paso 7: crear punto de guardado**

```bash
git add resources/views/layouts resources/views/componentes public/css resources/views/*/*.blade.php tests/Feature/NavegacionTest.php
git commit -m "refactor: unificar la estructura visual de la aplicación"
```

### Tarea 2: Navegación, acceso y panel

**Archivos:**
- Modificar: `resources/views/componentes/navegacion.blade.php`
- Modificar: `resources/views/autenticacion/ingresar.blade.php`
- Modificar: `resources/views/panel.blade.php`
- Modificar: `resources/css/app.css`
- Modificar: `tests/Feature/AutenticacionTest.php`
- Modificar: `tests/Feature/PanelReportesTest.php`

**Interfaces:**
- Consume: tokens, layout, botones y encabezado de la Tarea 1.
- Produce: navegación responsive y patrón de tarjetas usado por el resto del sistema.

- [ ] **Paso 1: agregar comprobaciones estructurales**

Comprobar que el panel conserva periodo, indicadores y cierre de sesión, y que el acceso tiene etiquetas y marca corporativa:

```php
$this->actingAs($usuario)->get('/panel')
    ->assertSee('aria-current="page"', false)
    ->assertSee('cuadricula-indicadores', false)
    ->assertSee('Cerrar sesión');

$this->get('/iniciar-sesion')
    ->assertSee('logo-comunitec-transparente.png', false)
    ->assertSee('for="correo"', false)
    ->assertSee('for="contrasena"', false);
```

- [ ] **Paso 2: rediseñar navegación y acceso**

Conservar el arreglo de enlaces y sus condiciones por rol. Agregar agrupación visual, nombre del usuario y formulario de salida alineado. Adaptar el acceso a los mismos tokens mediante clases semánticas, sin cambiar la acción ni campos.

- [ ] **Paso 3: reorganizar el panel**

Usar el encabezado compartido, una barra compacta para el periodo, tarjetas coherentes y dos columnas adaptables para actividad y alertas. Mantener exactamente los enlaces y cifras actuales.

- [ ] **Paso 4: verificar funcionalidad y responsive**

Ejecutar:

```bash
php artisan test tests/Feature/AutenticacionTest.php tests/Feature/NavegacionTest.php tests/Feature/PanelReportesTest.php --stop-on-failure
npm run build
php artisan view:cache
```

Revisar `/iniciar-sesion` y `/panel` a 320, 768, 1024 y 1440 px; comprobar ausencia de errores en consola.

- [ ] **Paso 5: crear punto de guardado**

```bash
git add resources/views/componentes/navegacion.blade.php resources/views/autenticacion/ingresar.blade.php resources/views/panel.blade.php resources/css/app.css public/css
git commit -m "feat: renovar navegación acceso y panel"
```

### Tarea 3: Clientes, catálogo, inventario y usuarios

**Archivos:**
- Modificar: `resources/views/clientes/listado.blade.php`
- Modificar: `resources/views/catalogo/listado.blade.php`
- Modificar: `resources/views/inventario/listado.blade.php`
- Modificar: `resources/views/administracion/usuarios/listado.blade.php`
- Modificar: `tests/Feature/ClientesTest.php`
- Modificar: `tests/Feature/CatalogoTest.php`
- Modificar: `tests/Feature/InventarioTest.php`
- Modificar: `tests/Feature/AdministracionUsuariosTest.php`

**Interfaces:**
- Consume: layout, botones, badges, alertas, tablas y formularios globales.
- Produce: patrón definitivo de módulos CRUD.

- [ ] **Paso 1: agregar pruebas de preservación de formularios y acciones**

Añadir aserciones estructurales a las pruebas existentes:

```php
->assertSee('class="formulario-administrativo', false)
->assertSee('class="contenedor-tabla', false)
->assertSee('class="grupo-acciones', false);
```

Para inventario, verificar además las etiquetas `Disponible`, `Reservada` y `Entregada` dentro del componente de insignia.

- [ ] **Paso 2: migrar clientes**

Reemplazar placeholders como única explicación por labels visibles, distribuir el formulario en cuadrícula y presentar el historial en tabla responsive. Conservar edición, valores anteriores y métodos HTTP existentes.

- [ ] **Paso 3: migrar catálogo e inventario**

Aplicar encabezados compartidos, cuadrículas de formularios, badges para tipo/estado, botones uniformes y contenedores de tabla. No modificar el filtrado de artículos activos ni el manejo de piezas.

- [ ] **Paso 4: migrar usuarios**

Unificar creación, edición, activación y desactivación. Las acciones administrativas deben conservar sus formularios independientes y usar botones compactos dentro de `.grupo-acciones`.

- [ ] **Paso 5: verificar módulos CRUD**

```bash
php artisan test tests/Feature/ClientesTest.php tests/Feature/CatalogoTest.php tests/Feature/InventarioTest.php tests/Feature/AdministracionUsuariosTest.php --stop-on-failure
php artisan view:cache
git diff --check
```

Revisar visualmente cada módulo con estado vacío, registros existentes y error de validación.

- [ ] **Paso 6: crear punto de guardado**

```bash
git add resources/views/clientes resources/views/catalogo resources/views/inventario resources/views/administracion tests/Feature
git commit -m "feat: unificar módulos administrativos"
```

### Tarea 4: Flujo completo de cotizaciones

**Archivos:**
- Modificar: `resources/views/cotizaciones/listado.blade.php`
- Modificar: `resources/views/cotizaciones/detalle.blade.php`
- Modificar: `tests/Feature/CotizacionesTest.php`
- Modificar: `tests/Feature/EdicionCotizacionesTest.php`
- Modificar: `tests/Feature/PermisosCotizacionesTest.php`

**Interfaces:**
- Consume: todos los componentes visuales base.
- Produce: patrón para barras de acciones, formularios complejos y tablas editables.

- [ ] **Paso 1: agregar pruebas para los controles críticos**

Comprobar que las respuestas siguen incluyendo las acciones según estado y rol:

```php
$respuesta->assertSee('Generar PDF')
    ->assertSee('Agregar partida')
    ->assertSee('class="grupo-acciones', false);
```

La prueba del rol Consulta debe seguir demostrando que no aparecen formularios de escritura.

- [ ] **Paso 2: convertir el listado en tabla uniforme**

Mostrar folio, cliente, fecha, total, estado con badge y acción `Ver detalle` como botón compacto. Mantener el formulario de nueva cotización y su comportamiento con lista de clientes vacía.

- [ ] **Paso 3: ordenar el encabezado y acciones del detalle**

Agrupar navegación, PDF, correo, aceptación, rechazo, cancelación y conversión a venta respetando todas las condiciones existentes. Aplicar variantes por significado y evitar formularios de distinto tamaño dentro del grupo.

- [ ] **Paso 4: ordenar encabezado editable, nueva partida y partidas existentes**

Usar cuadrículas responsive, labels visibles y una tabla con acciones compactas. Conservar IDs, nombres, reglas `min`, `max`, `step`, campos ocultos y relaciones de cada formulario.

- [ ] **Paso 5: verificar todos los estados**

```bash
php artisan test tests/Feature/CotizacionesTest.php tests/Feature/EdicionCotizacionesTest.php tests/Feature/PermisosCotizacionesTest.php --stop-on-failure
php artisan view:cache
```

Revisar visualmente borrador, pendiente, aceptada, rechazada, vencida, cancelada y convertida; comprobar selectores abiertos y tablas a 320 y 1440 px.

- [ ] **Paso 6: crear punto de guardado**

```bash
git add resources/views/cotizaciones tests/Feature/CotizacionesTest.php tests/Feature/EdicionCotizacionesTest.php tests/Feature/PermisosCotizacionesTest.php
git commit -m "feat: renovar la experiencia de cotizaciones"
```

### Tarea 5: Ventas, reportes y paginación

**Archivos:**
- Modificar: `resources/views/ventas/listado.blade.php`
- Modificar: `resources/views/ventas/detalle.blade.php`
- Modificar: `resources/views/reportes/cotizaciones.blade.php`
- Modificar: `resources/views/reportes/ventas.blade.php`
- Modificar: `resources/views/componentes/paginacion.blade.php`
- Modificar: `tests/Feature/VentasTest.php`
- Modificar: `tests/Feature/PanelReportesTest.php`

**Interfaces:**
- Consume: patrones de tablas, acciones, filtros y resúmenes definidos en tareas anteriores.
- Produce: sistema visual completo de consulta y reportes.

- [ ] **Paso 1: reforzar pruebas de estructura y filtros**

Añadir aserciones para pestaña activa, filtros etiquetados, totales y acciones de detalle:

```php
->assertSee('class="pestanas-reportes', false)
->assertSee('class="formulario-filtros', false)
->assertSee('Aplicar filtros')
->assertSee('Ver detalle');
```

- [ ] **Paso 2: migrar ventas**

Aplicar tabla y botones compartidos al listado. En el detalle, usar una cuadrícula de datos, badge de venta cerrada, tabla responsive y resumen de importes alineado.

- [ ] **Paso 3: migrar reportes y paginación**

Convertir pestañas y navegación de páginas a botones discretos, mantener parámetros de consulta y `aria-current`. Uniformar filtros, totales y tablas sin cambiar consultas ni paginadores.

- [ ] **Paso 4: verificar consultas y navegación**

```bash
php artisan test tests/Feature/VentasTest.php tests/Feature/PanelReportesTest.php --stop-on-failure
php artisan view:cache
git diff --check
```

Revisar ambos reportes con filtros vacíos y combinados, paginación y tablas amplias en móvil.

- [ ] **Paso 5: crear punto de guardado**

```bash
git add resources/views/ventas resources/views/reportes resources/views/componentes/paginacion.blade.php tests/Feature/VentasTest.php tests/Feature/PanelReportesTest.php
git commit -m "feat: unificar ventas y reportes"
```

### Tarea 6: Auditoría visual y cierre

**Archivos:**
- Modificar: vistas o estilos detectados durante la auditoría, limitados a correcciones visuales.
- Modificar: `AGENTS.md` para registrar la entrega terminada y su evidencia.

**Interfaces:**
- Consume: aplicación visual completa.
- Produce: entrega revisada y documentada.

- [ ] **Paso 1: auditar enlaces, controles y duplicación**

Ejecutar búsquedas:

```bash
rg -n '<a[^>]*>(Editar|Eliminar|Ver|Detalles|Crear|Agregar|Guardar|Cancelar|Regresar|Generar PDF|Nueva cotización|Nuevo cliente|Registrar venta)' resources/views
rg -n '<style|style=' resources/views --glob '!cotizaciones/pdf.blade.php' --glob '!correos/**'
rg -n '<html|<head|<body' resources/views --glob '!layouts/**' --glob '!cotizaciones/pdf.blade.php' --glob '!correos/**' --glob '!autenticacion/ingresar.blade.php'
```

Corregir solamente casos reales: enlaces operativos sin botón, estilos duplicados o documentos que no usan el layout.

- [ ] **Paso 2: ejecutar verificación funcional completa**

```bash
composer formato
php artisan test
php artisan view:cache
composer formato:verificar
npm run build
git diff --check
```

Resultado esperado: todas las pruebas, Blade, formato y compilación pasan.

- [ ] **Paso 3: revisar visualmente toda la aplicación**

Con los roles Administrador, Comercial y Consulta, recorrer inicio de sesión, panel, clientes, catálogo, inventario, cotizaciones, ventas, reportes y usuarios. En 320, 768, 1024 y 1440 px comprobar:

- Navegación y estado activo.
- Foco visible por teclado.
- Botones alineados y con altura uniforme.
- Formularios sin campos amontonados.
- Tablas contenidas y desplazables.
- Badges legibles.
- Ausencia de desbordamiento horizontal del documento.
- Consola sin errores.

- [ ] **Paso 4: registrar evidencia**

Actualizar `AGENTS.md` con fecha, alcance, comandos ejecutados, cantidad de pruebas y cualquier limitación observada. No incluir credenciales ni datos personales.

- [ ] **Paso 5: revisión del diff y commit final**

```bash
git diff --stat
git diff --check
git status --short
git add AGENTS.md resources/views public/css resources/css tests/Feature
git commit -m "feat: completar renovación visual integral"
```

- [ ] **Paso 6: revisión de rama**

Aplicar `superpowers:requesting-code-review` y corregir cualquier problema funcional, responsive, accesible o de consistencia antes de declarar la entrega terminada.
