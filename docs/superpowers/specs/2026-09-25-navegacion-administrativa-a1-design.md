# Diseño: navegación administrativa tipo A1 y perfil de usuario

## Objetivo

Reorganizar la interfaz de COMUN&TEC como un sistema administrativo de escritorio: menú lateral jerárquico, barra superior contextual y cada función ubicada dentro de su módulo. La actualización conservará la identidad corporativa, las rutas de negocio y los permisos existentes.

## Principio del flujo comercial

La venta nunca se registra directamente. El recorrido obligatorio continúa siendo:

1. Crear la cotización y agregar productos o servicios.
2. Enviar la cotización al cliente.
3. Aceptar la cotización, reservando únicamente la cantidad necesaria del stock.
4. Abrir la acción de conversión de la cotización aceptada.
5. Registrar el método de pago administrativo.
6. Al final del formulario de venta, capturar los números de serie de los productos marcados como serializables.
7. Crear la venta y asociar permanentemente cada serie con su producto, partida y venta.

La aceptación no captura ni selecciona números de serie. Los servicios, conceptos libres y productos configurados sin serie no generan campos de serie. La cantidad de campos debe coincidir exactamente con la cantidad vendida.

## Estructura de navegación

### Menú lateral

- Inicio
  - Panel.
- Cotizaciones
  - Nueva cotización.
  - Historial de cotizaciones.
- Ventas
  - Cotizaciones aceptadas listas para convertir.
  - Historial de ventas.
- Clientes
  - Registrar cliente.
  - Consultar clientes.
- Inventario
  - Productos y servicios.
  - Categorías.
  - Series vendidas, como sección del inventario y detalle de venta.
- Reportes
  - Cotizaciones.
  - Ventas.
- Administración, visible únicamente para administradores
  - Usuarios.
- Mi perfil
  - Datos personales.
  - Seguridad de la cuenta.

Los grupos con más de una opción serán desplegables. El grupo de la ruta activa permanecerá abierto. Los módulos sin acciones separadas navegarán directamente a su página.

### Barra superior

La barra superior mostrará:

- botón para abrir o contraer el menú;
- nombre del módulo actual;
- ruta contextual breve;
- nombre y rol del usuario;
- acceso a Mi perfil;
- cierre de sesión.

No repetirá toda la navegación lateral.

## Comportamiento adaptable

- Escritorio: menú lateral fijo y contenido desplazado a su derecha.
- Tableta: menú lateral contraíble con estado controlado en la interfaz.
- Celular: panel lateral superpuesto, fondo de cierre, botón de menú y cierre automático al navegar.
- El contenido y las tablas conservarán su comportamiento responsive actual.
- Los controles tendrán objetivos táctiles de al menos 44 píxeles y foco visible.

## Componentes de interfaz

- `layouts/aplicacion.blade.php`: estructura general del área autenticada.
- `componentes/navegacion-lateral.blade.php`: grupos, permisos, rutas activas y subopciones.
- `componentes/barra-superior.blade.php`: contexto, usuario y acciones de sesión.
- `components/icono.blade.php`: iconos SVG reutilizables y decorativos.
- JavaScript pequeño y local para abrir/cerrar el menú y conservar accesibilidad mediante `aria-expanded` y `aria-controls`.
- `public/css/navegacion.css`: distribución, estados, responsive y animaciones moderadas.

Se reutilizarán la paleta, botones, formularios, tablas, alertas y tipografía existentes.

## Organización funcional

Las páginas actuales seguirán siendo la autoridad de cada operación. Los accesos del menú dirigirán a secciones o anclas claras dentro de esas páginas cuando no exista una ruta independiente. No se duplicarán formularios ni controladores para simular submódulos.

- Nueva cotización llevará al formulario de alta de Cotizaciones.
- Historial llevará al listado de Cotizaciones.
- Cotizaciones listas para venta llevará a Cotizaciones filtradas por estado aceptada.
- Inventario permitirá diferenciar productos y servicios mediante filtros.
- Series vendidas llevará a la sección correspondiente del Inventario.

Los filtros se incorporarán a rutas existentes con parámetros de consulta cuando sea necesario.

## Mi perfil

Se creará un controlador y una vista exclusivos del usuario autenticado.

### Datos personales

- nombre obligatorio;
- correo obligatorio, válido y único salvo para el propio usuario;
- el rol será visible, pero no editable desde el perfil.

### Contraseña

- contraseña actual obligatoria;
- contraseña nueva con confirmación;
- validación mediante el hash almacenado;
- la contraseña nueva se guardará cifrada;
- errores y confirmaciones se mostrarán con los componentes existentes.

Modificar el perfil no permitirá cambiar el rol ni el estado activo.

## Permisos

- Administrador: todos los módulos, Administración y Mi perfil.
- Comercial: Panel, Cotizaciones, Ventas, Clientes, Inventario, Reportes y Mi perfil.
- Consulta: Panel, historial de Cotizaciones, Ventas, Reportes y Mi perfil, sin enlaces ni formularios de escritura.

La visibilidad del menú acompañará los permisos del servidor; no sustituirá los middleware actuales.

## Compatibilidad

- No cambiar modelos comerciales, migraciones del inventario, cálculos, reservas, PDF, correo ni conversión a venta.
- No agregar venta directa, carrito, pasarela, cobranza o facturación.
- Conservar nombres de rutas existentes siempre que sea posible.
- Los enlaces anteriores seguirán funcionando.

## Pruebas y aceptación

- Los tres roles reciben únicamente los enlaces autorizados.
- El grupo activo se identifica visualmente y mediante atributos accesibles.
- Los submenús funcionan con teclado, ratón y toque.
- La navegación funciona en 320, 768, 1024 y 1440 píxeles sin desbordamiento del documento.
- Mi perfil actualiza nombre y correo sin modificar rol o estado.
- El cambio de contraseña exige la contraseña actual y confirmación.
- El acceso “Registrar venta” solo muestra cotizaciones aceptadas y la captura de series sigue ocurriendo al final de la conversión.
- Todas las pruebas existentes continúan aprobadas.
- Se ejecutan Pint, caché de Blade, pruebas, revisión de rutas, `git diff --check` y compilación frontend cuando el entorno lo permita.

## Estrategia de Git

La especificación, el plan, la implementación y las pruebas se guardarán en un único commit al finalizar, por petición del usuario. Los archivos temporales ya existentes permanecerán fuera del commit.
