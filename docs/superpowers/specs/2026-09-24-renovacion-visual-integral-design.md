# Renovación visual integral de COMUN&TEC

## Objetivo

Unificar toda la interfaz web administrativa con una apariencia empresarial, tecnológica y coherente con la identidad de COMUN&TEC. La renovación conservará rutas, permisos, validaciones, formularios, controladores, modelos y reglas de negocio.

## Alcance

La renovación comprende inicio de sesión, panel, clientes, catálogo, inventario, cotizaciones, ventas, reportes y administración de usuarios. También incluye navegación, alertas, paginación, estados vacíos y mensajes de validación.

El PDF de cotización y la plantilla de correo conservarán sus estilos propios porque responden a formatos de documento distintos de la aplicación web.

## Dirección visual

La interfaz usará el verde azulado del logotipo como color principal, acompañado por tonos más oscuros de la misma familia para texto y navegación. Los fondos serán blancos y grises fríos muy claros; los bordes serán sutiles. Los colores semánticos se limitarán a éxito, advertencia y peligro cuando comuniquen el estado de una operación.

La presentación tendrá jerarquía tipográfica clara, sombras suaves, radios consistentes y una escala común de espaciado. Se evitarán degradados, efectos decorativos exagerados y colores ajenos a la identidad corporativa.

## Arquitectura visual

### Layout principal

Se creará un layout Blade compartido para las páginas autenticadas. El layout será responsable de:

- Metadatos y carga de estilos.
- Navegación principal.
- Contenedor general de contenido.
- Mensajes de sesión globales.
- Regiones extensibles para título, acciones y contenido.

Las vistas dejarán de repetir el documento HTML completo y usarán el layout. La pantalla de acceso tendrá un layout visualmente relacionado, pero simplificado.

### Componentes reutilizables

Se crearán o consolidarán componentes Blade para:

- Botones y enlaces con apariencia de botón.
- Badges de estado.
- Alertas y errores de validación.
- Encabezados de página.
- Contenedores de tablas.
- Estados vacíos cuando aporten una reducción real de duplicación.

Los componentes admitirán únicamente las variantes necesarias: principal, secundaria, éxito, peligro, advertencia y contorno. Los iconos serán SVG pequeños, con `aria-hidden` cuando el texto ya describa la acción. No se agregará una dependencia de iconos.

### CSS global

Los estilos visuales se centralizarán en los recursos globales existentes. Se definirán variables para colores, radios, sombras, anchuras y espaciado. Las clases repetidas de botones, formularios, tarjetas, tablas, badges y acciones reemplazarán reglas específicas por página.

No se introducirán estilos en línea ni copias extensas de las mismas reglas en diferentes vistas.

## Elementos del sistema

### Navegación

La navegación conservará exactamente las opciones permitidas por cada rol. Tendrá marca visible, agrupación clara, estado activo y cierre de sesión integrado. En pantallas pequeñas podrá envolver sus elementos sin provocar desplazamiento horizontal.

### Botones y acciones

Todas las acciones operativas importantes se mostrarán como botones o enlaces con apariencia de botón. Compartirán altura mínima de 44 píxeles, tipografía, padding, radio, foco visible y alineación.

Las acciones destructivas usarán la variante de peligro; la acción principal de cada bloque usará el color corporativo; las acciones secundarias y de navegación usarán variantes discretas. Los grupos de acciones permitirán ajuste de línea y conservarán separaciones constantes.

### Formularios

Inputs, selects y textareas compartirán altura, borde, radio y foco. Cada control tendrá etiqueta visible. Los formularios usarán cuadrículas adaptables y pasarán a una columna en móvil. Los errores se mostrarán cerca del contexto correspondiente o dentro de la alerta global existente.

No se modificarán nombres de campos, métodos, destinos, límites, atributos de validación ni valores enviados.

### Tablas

Todas las tablas usarán encabezados contrastados, filas espaciadas, divisores sutiles y hover ligero. Sus contenedores permitirán desplazamiento horizontal y comunicarán esta posibilidad visualmente en pantallas pequeñas.

Los estados se presentarán mediante badges. Las acciones se agruparán en una celda estable y usarán botones compactos de igual altura. Las filas no dependerán de botones con tamaños distintos.

### Tarjetas y panel

Las tarjetas de indicadores compartirán estructura, borde, sombra y jerarquía. Los indicadores enlazables tendrán estados hover y focus visibles. El panel mantendrá los datos y vínculos existentes, pero ordenará mejor filtros, métricas, actividad y alertas.

### Páginas de detalle

Los detalles de cotización y venta usarán bloques de información, barra de acciones y resúmenes de importes consistentes. Las numerosas acciones de una cotización se agruparán por su propósito sin cambiar su disponibilidad ni las condiciones actuales.

## Adaptación por módulo

- **Inicio de sesión:** tarjeta corporativa, campos y botón coherentes con el sistema.
- **Panel:** encabezado, selector de periodo, indicadores y actividad uniformes.
- **Clientes:** formulario etiquetado y listado convertido a presentación tabular adaptable.
- **Catálogo:** conservar su formulario completo, mejorar jerarquía, tabla, estados y acción de activar o desactivar.
- **Inventario:** formularios separados con jerarquía clara y tabla con badges de disponibilidad.
- **Cotizaciones:** tabla de listado, formulario de creación, detalle, partidas y acciones alineadas.
- **Ventas:** listado y detalle con botones, tablas y resumen de importes compartidos.
- **Reportes:** pestañas, filtros, tablas, totales y paginación coherentes.
- **Usuarios:** formulario y tabla ajustados al mismo sistema visual.

## Accesibilidad

- Contraste suficiente para texto, fondos, bordes y botones.
- Foco visible en controles, enlaces y botones.
- Objetivos interactivos con altura mínima de 44 píxeles cuando corresponda.
- Conservación de etiquetas, encabezados de tabla, regiones y atributos ARIA existentes.
- No depender únicamente del color para comunicar estados.
- Respeto de `prefers-reduced-motion` para cualquier transición.

## Diseño responsive

La revisión cubrirá 320, 768, 1024 y 1440 píxeles. En móvil:

- Encabezados y grupos de acciones se reorganizarán verticalmente.
- Los botones principales podrán ocupar todo el ancho.
- Los formularios pasarán a una columna.
- Las tablas conservarán desplazamiento horizontal dentro de su contenedor.
- La navegación permitirá acceso a todos los módulos autorizados sin desbordar la página.

## Restricciones funcionales

No se cambiarán controladores, modelos, migraciones, rutas, consultas, permisos, validaciones ni transiciones de estado. Cualquier ajuste Blade conservará nombres, métodos, tokens CSRF, directivas de autorización y condiciones actuales.

Los vínculos de datos dentro de tablas pueden conservarse como enlaces cuando su función sea navegación contextual. Las acciones explícitas como ver detalle, editar, guardar, cancelar, descargar PDF o registrar una operación tendrán tratamiento de botón.

## Implementación incremental

1. Crear tokens, estilos globales, layout y componentes base.
2. Migrar navegación, acceso y panel.
3. Migrar clientes, catálogo, inventario y usuarios.
4. Migrar listados, detalle y formularios de cotizaciones.
5. Migrar ventas y reportes.
6. Revisar todas las vistas y eliminar estilos redundantes.
7. Ejecutar pruebas funcionales, compilación y revisión visual responsive.

Cada incremento debe conservar la suite existente en verde antes de continuar.

## Criterios de aceptación

- Todas las páginas web autenticadas comparten layout, paleta y escala de espaciado.
- Las acciones importantes se reconocen como botones y mantienen tamaños coherentes.
- Formularios, tablas, tarjetas, badges, alertas y paginación usan patrones globales.
- La navegación marca correctamente la sección activa y respeta permisos.
- No hay desbordamiento horizontal del documento en los anchos de prueba.
- Las tablas anchas se desplazan dentro de su propio contenedor.
- Los controles mantienen etiquetas, foco visible y contraste legible.
- No cambia el comportamiento funcional ni la información enviada por formularios.
- Las pruebas PHP, formato, compilación Blade y compilación frontend pasan.
- La revisión visual cubre todos los módulos disponibles para los roles existentes.
