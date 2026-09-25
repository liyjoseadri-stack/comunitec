# Navegación administrativa tipo A1 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Convertir la interfaz autenticada de COMUN&TEC en un sistema administrativo con menú lateral jerárquico, barra superior contextual y perfil seguro, conservando el flujo comercial existente.

**Architecture:** El layout Blade compartido alojará dos componentes independientes: navegación lateral y barra superior. Las subopciones reutilizarán rutas y filtros existentes; el perfil tendrá un controlador propio con dos operaciones separadas para datos personales y contraseña. CSS y JavaScript nativos manejarán el estado responsive sin agregar dependencias.

**Tech Stack:** Laravel 12, PHP 8.2+, Blade, CSS nativo, JavaScript nativo, PHPUnit, MySQL.

**Spec:** `docs/superpowers/specs/2026-09-25-navegacion-administrativa-a1-design.md`

## Global Constraints

- Toda la interfaz y el código propio permanecerán en español.
- No cambiar modelos comerciales, cálculos, reservas, PDF, correo ni conversión a venta.
- Los números de serie se capturan únicamente al final de la conversión de una cotización aceptada en venta.
- No agregar ventas directas, carrito, pasarela, cobranza ni facturación.
- Mantener los permisos de servidor para administrador, comercial y consulta.
- Reutilizar la paleta y los componentes visuales actuales.
- Crear un único commit al finalizar toda la implementación.
- No incluir `output/` ni archivos `work-entry*.php`.

## Review Focus

- Una ruta hija activa debe abrir su grupo lateral y marcar un solo enlace con `aria-current="page"`.
- El menú móvil debe cerrar con el botón, el fondo, Escape y después de elegir un enlace.
- Un usuario no puede cambiar su rol o estado mediante campos manipulados del formulario de perfil.
- El correo del perfil debe seguir siendo único y permitir conservar el correo del propio usuario.
- Cambiar contraseña debe rechazar una contraseña actual incorrecta y conservar el hash anterior.

---

### Task 1: Contrato de navegación y filtros de acceso rápido

**Files:**
- Modify: `app/Http/Controllers/ControladorCotizaciones.php`
- Modify: `app/Http/Controllers/ControladorProductos.php`
- Modify: `resources/views/cotizaciones/listado.blade.php`
- Modify: `resources/views/inventario/productos/listado.blade.php`
- Test: `tests/Feature/NavegacionAdministrativaTest.php`

**Interfaces:**
- Consumes: rutas `cotizaciones.listado` e `inventario.listado`.
- Produces: filtros GET `estado=aceptada`, `seccion=nueva`, `tipo=producto|servicio` y anclas estables `#nueva-cotizacion`, `#nuevo-cliente`, `#series-vendidas`.

- [ ] **Step 1: Write the failing navigation filter tests**

```php
public function test_el_acceso_de_ventas_lista_solo_cotizaciones_aceptadas(): void
{
    $this->actingAs($this->comercial())
        ->get(route('cotizaciones.listado', ['estado' => 'aceptada']))
        ->assertOk()
        ->assertSee('Lista para venta')
        ->assertDontSee('COT-PENDIENTE');
}

public function test_inventario_admite_filtro_de_tipo_desde_el_menu(): void
{
    $this->actingAs($this->comercial())
        ->get(route('inventario.listado', ['tipo' => 'servicio']))
        ->assertOk()
        ->assertSee('Servicio de instalación')
        ->assertDontSee('Equipo físico');
}
```

- [ ] **Step 2: Run the tests and verify the unfiltered behavior fails**

Run: `php artisan test --filter=NavegacionAdministrativaTest`

Expected: FAIL porque Cotizaciones aún ignora `estado` y faltan identificadores de sección.

- [ ] **Step 3: Implement validated filters and stable anchors**

En `ControladorCotizaciones::listar(Request $solicitud)`, validar `estado` con `Rule::in(Cotizacion::estados())` y aplicar:

```php
$consulta = Cotizacion::query()->with('venta', 'cliente');
$consulta->when(
    $datos['estado'] ?? null,
    fn ($consulta, $estado) => $consulta->where('estado', $estado)
);
```

Añadir los identificadores a las secciones existentes y conservar los filtros de Productos.

- [ ] **Step 4: Run focused tests**

Run: `php artisan test --filter="NavegacionAdministrativaTest|CotizacionesTest|ProductosInventarioTest"`

Expected: PASS.

### Task 2: Perfil autenticado y cambio seguro de contraseña

**Files:**
- Create: `app/Http/Controllers/ControladorPerfil.php`
- Create: `resources/views/perfil/mostrar.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/PerfilUsuarioTest.php`

**Interfaces:**
- Consumes: `Request::user()` y campos `usuarios.nombre`, `usuarios.correo`, `usuarios.contrasena`.
- Produces: rutas `perfil.mostrar`, `perfil.actualizar` y `perfil.contrasena`.

- [ ] **Step 1: Write failing profile tests**

```php
public function test_usuario_actualiza_solo_nombre_y_correo(): void
{
    $usuario = Usuario::factory()->create(['rol' => Usuario::ROL_COMERCIAL]);

    $this->actingAs($usuario)->put(route('perfil.actualizar'), [
        'nombre' => 'Nombre actualizado',
        'correo' => 'actualizado@example.test',
        'rol' => Usuario::ROL_ADMINISTRADOR,
        'activo' => false,
    ])->assertRedirect(route('perfil.mostrar'));

    $usuario->refresh();
    $this->assertSame(Usuario::ROL_COMERCIAL, $usuario->rol);
    $this->assertTrue($usuario->activo);
}

public function test_contrasena_actual_incorrecta_no_cambia_el_hash(): void
{
    $usuario = Usuario::factory()->create(['contrasena' => Hash::make('Anterior2026!')]);
    $hash = $usuario->contrasena;

    $this->actingAs($usuario)->put(route('perfil.contrasena'), [
        'contrasena_actual' => 'Incorrecta',
        'contrasena' => 'Nueva2026!',
        'contrasena_confirmation' => 'Nueva2026!',
    ])->assertSessionHasErrors('contrasena_actual');

    $this->assertSame($hash, $usuario->fresh()->contrasena);
}
```

- [ ] **Step 2: Run tests and verify routes are missing**

Run: `php artisan test --filter=PerfilUsuarioTest`

Expected: FAIL con rutas `perfil.*` inexistentes.

- [ ] **Step 3: Implement focused controller actions**

```php
public function actualizar(Request $solicitud): RedirectResponse
{
    $usuario = $solicitud->user();
    $usuario->update($solicitud->validate([
        'nombre' => ['required', 'string', 'max:255'],
        'correo' => ['required', 'email', 'max:255', Rule::unique('usuarios', 'correo')->ignore($usuario)],
    ]));

    return to_route('perfil.mostrar')->with('estado', 'Perfil actualizado.');
}
```

Para contraseña, validar `current_password:sesion`, `confirmed`, mínimo 8 y actualizar únicamente `contrasena`. Registrar las tres rutas dentro de `auth`, disponibles para todos los roles.

- [ ] **Step 4: Build the profile view**

Crear dos formularios independientes, mostrar rol como insignia no editable y usar los componentes de botón, alerta y validación existentes.

- [ ] **Step 5: Run profile and authentication tests**

Run: `php artisan test --filter="PerfilUsuarioTest|AutenticacionTest|AdministracionUsuariosTest"`

Expected: PASS.

### Task 3: Componentes del shell administrativo

**Files:**
- Create: `resources/views/componentes/navegacion-lateral.blade.php`
- Create: `resources/views/componentes/barra-superior.blade.php`
- Create: `resources/views/components/icono.blade.php`
- Modify: `resources/views/layouts/aplicacion.blade.php`
- Delete after replacement: `resources/views/componentes/navegacion.blade.php`
- Test: `tests/Feature/NavegacionAdministrativaTest.php`

**Interfaces:**
- Consumes: rutas de Tasks 1–2 y predicados `Usuario::esAdministrador()`, `esComercial()`, `esConsulta()`.
- Produces: `#navegacion-lateral`, `#boton-menu`, `.barra-superior`, grupos con `<details>` y enlace activo accesible.

- [ ] **Step 1: Add failing role and accessibility assertions**

```php
$this->actingAs($administrador)->get('/panel')
    ->assertSee('id="navegacion-lateral"', false)
    ->assertSee('aria-label="Navegación administrativa"', false)
    ->assertSee('Mi perfil')
    ->assertSee('Administración');

$this->actingAs($consulta)->get('/panel')
    ->assertDontSee('Clientes')
    ->assertDontSee('Inventario')
    ->assertDontSee('Administración')
    ->assertSee('Mi perfil');
```

- [ ] **Step 2: Run the navigation test and confirm old Navbar fails**

Run: `php artisan test --filter=NavegacionAdministrativaTest`

Expected: FAIL por ausencia del shell lateral.

- [ ] **Step 3: Implement the icon component**

El componente aceptará `nombre` y renderizará SVG de 20×20 con `aria-hidden="true"`. Incluir: inicio, documento, venta, usuarios, inventario, reporte, configuración, perfil, menú, cerrar y flecha.

- [ ] **Step 4: Implement the lateral navigation**

Usar grupos `<details>` abiertos cuando `request()->routeIs()` coincida. Crear URL con filtros y anclas:

```php
route('cotizaciones.listado').'#nueva-cotizacion'
route('cotizaciones.listado', ['estado' => 'aceptada'])
route('inventario.listado', ['tipo' => 'producto'])
route('inventario.listado', ['tipo' => 'servicio'])
route('inventario.listado').'#series-vendidas'
```

- [ ] **Step 5: Implement the top bar and layout**

La barra recibirá el título mediante `@yield('titulo')`, mostrará nombre/rol y contendrá formularios o enlaces válidos para Perfil y cierre de sesión. El layout envolverá lateral, barra y contenido en `.estructura-aplicacion`.

- [ ] **Step 6: Run navigation and authorization tests**

Run: `php artisan test --filter="NavegacionAdministrativaTest|NavegacionTest|AutorizacionRolesTest"`

Expected: PASS después de adaptar las aserciones del Navbar anterior al shell nuevo.

### Task 4: Diseño responsive e interacción nativa

**Files:**
- Replace: `public/css/navegacion.css`
- Modify: `public/css/administracion.css`
- Create: `public/js/navegacion.js`
- Modify: `resources/views/layouts/aplicacion.blade.php`
- Test: `tests/Feature/NavegacionAdministrativaTest.php`

**Interfaces:**
- Consumes: identificadores y clases de Task 3.
- Produces: menú fijo de 17rem en escritorio, panel superpuesto debajo de 64rem y controles de 44px.

- [ ] **Step 1: Add failing asset and semantics assertions**

```php
$this->actingAs($usuario)->get('/panel')
    ->assertSee('css/navegacion.css', false)
    ->assertSee('js/navegacion.js', false)
    ->assertSee('aria-controls="navegacion-lateral"', false)
    ->assertSee('aria-expanded="false"', false);
```

- [ ] **Step 2: Implement desktop and mobile CSS**

Definir variables `--ancho-lateral: 17rem` y `--alto-barra: 4rem`; usar CSS Grid para el shell. En móvil, transformar el lateral con `translateX(-100%)`, activarlo mediante `.menu-abierto`, mostrar fondo y bloquear desplazamiento del cuerpo. Respetar `prefers-reduced-motion`.

- [ ] **Step 3: Implement menu behavior**

```js
const establecerMenu = (abierto) => {
    document.body.classList.toggle('menu-abierto', abierto);
    boton.setAttribute('aria-expanded', String(abierto));
};

boton.addEventListener('click', () => establecerMenu(!document.body.classList.contains('menu-abierto')));
fondo.addEventListener('click', () => establecerMenu(false));
document.addEventListener('keydown', (evento) => {
    if (evento.key === 'Escape') establecerMenu(false);
});
```

Cerrar después de activar un enlace en ancho móvil y restablecer el estado al regresar a escritorio.

- [ ] **Step 4: Run tests and compile views**

Run: `php artisan test --filter=NavegacionAdministrativaTest; php artisan view:cache`

Expected: PASS.

### Task 5: Integración visual de módulos y acciones

**Files:**
- Modify: `resources/views/cotizaciones/listado.blade.php`
- Modify: `resources/views/ventas/listado.blade.php`
- Modify: `resources/views/clientes/listado.blade.php`
- Modify: `resources/views/inventario/productos/listado.blade.php`
- Modify: `resources/views/inventario/categorias/listado.blade.php`
- Modify: `resources/views/reportes/cotizaciones.blade.php`
- Modify: `resources/views/reportes/ventas.blade.php`
- Modify: `resources/views/panel.blade.php`
- Test: `tests/Feature/NavegacionAdministrativaTest.php`
- Test: `tests/Feature/SeriesVentaTest.php`

**Interfaces:**
- Consumes: shell, filtros y anclas de Tasks 1–4.
- Produces: títulos, acciones y secciones consistentes con el módulo lateral.

- [ ] **Step 1: Add tests for links and preserved serial flow**

```php
$this->actingAs($comercial)->get('/panel')
    ->assertSee(route('cotizaciones.listado').'#nueva-cotizacion', false)
    ->assertSee(route('cotizaciones.listado', ['estado' => 'aceptada']), false);

$this->actingAs($comercial)->get(route('cotizaciones.detalle', $aceptada))
    ->assertSee('Registrar venta')
    ->assertSee('name="series['.$partida->id.'][]"', false);
```

- [ ] **Step 2: Align headings, anchor targets and empty states**

Separar encabezados de formulario e historial sin duplicar lógica. En la lista filtrada de aceptadas mostrar “Cotizaciones listas para venta”. Añadir `id="series-vendidas"` a la sección existente.

- [ ] **Step 3: Preserve conversion semantics**

No mover los campos `series[partida][]` fuera del formulario `ventas.guardar`. Mantenerlos después del método de pago y solo para `$partida->articulo?->requiere_numero_serie`.

- [ ] **Step 4: Run module regressions**

Run: `php artisan test --filter="NavegacionAdministrativaTest|SeriesVentaTest|VentasTest|CotizacionesTest|PanelReportesTest"`

Expected: PASS.

### Task 6: Verificación integral y commit único

**Files:**
- Modify: `AGENTS.md`
- Include: specification, plan, implementation and tests from Tasks 1–5.

**Interfaces:**
- Consumes: complete implementation.
- Produces: verified branch with one final commit.

- [ ] **Step 1: Run PHP formatting and complete tests**

Run:

```powershell
composer formato
php artisan test
php artisan view:cache
composer formato:verificar
git diff --check
```

Expected: all commands exit successfully.

- [ ] **Step 2: Verify routes and frontend build**

Run:

```powershell
php artisan route:list
php artisan migrate:status
npm run build
```

If esbuild is blocked only by the Codex sandbox parent-directory restriction, record the exact limitation and retain prior successful external-build evidence.

- [ ] **Step 3: Review in a real browser**

Verify administrator, commercial and consultation navigation at 320, 768, 1024 and 1440px. Confirm lateral opening/closing, active groups, profile validation, Cotizaciones, Ventas, Clientes, Inventario, Categories, Reports and Users. Check browser console for errors.

- [ ] **Step 4: Review security and regressions**

Confirm CSRF on every write form, role middleware, current-password validation, escaped Blade output, no editable role/status fields in profile, no direct-sale route and series remaining inside final sale conversion.

- [ ] **Step 5: Update project context**

Add a concise entry to `AGENTS.md` describing the administrative shell, profile, permission behavior, serial-number timing and verification evidence.

- [ ] **Step 6: Create the only commit**

```powershell
git add -- AGENTS.md app public/css public/js resources/views routes tests docs/superpowers/specs/2026-09-25-navegacion-administrativa-a1-design.md docs/superpowers/plans/2026-09-25-navegacion-administrativa-a1.md
git commit -m "feat: implementar navegacion administrativa tipo A1"
```

Before committing, verify with `git status --short` that `output/` and `work-entry*.php` remain untracked and excluded.

### Task 7: Separar Productos y Servicios

**Decisión posterior aprobada:** Productos y Servicios deben tener tablas, modelos, controladores, formularios y rutas distintos. Las partidas conservan referencias opcionales separadas y datos históricos. Stock, reservas y series pertenecen exclusivamente a Productos.

- [x] Crear tablas `productos` y `servicios` y retirar `articulos_catalogo`.
- [x] Cambiar partidas a `producto_id` o `servicio_id`.
- [x] Separar modelos, controladores, rutas, listados y formularios.
- [x] Adaptar categorías, cotizaciones, reservas, ventas, PDF y correo.
- [x] Reconstruir MySQL, ejecutar el seeder y verificar el flujo completo.
