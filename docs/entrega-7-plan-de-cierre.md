# Entrega 7: validación, documentación y cierre del proyecto

## Propósito

Cerrar la versión entregable de Comunitec mediante una revisión integral del sistema, pruebas del flujo comercial completo, validación con usuarios, documentación de operación y preparación de las evidencias académicas.

Esta entrega no agrega nuevos módulos comerciales. Su objetivo es demostrar que las funciones construidas en las entregas anteriores trabajan juntas, documentar su uso real y corregir defectos que impidan la entrega.

## Resultado esperado

Al terminar esta entrega deben cumplirse todas estas condiciones:

- El flujo desde el registro del cliente hasta la consulta de la venta funciona de principio a fin.
- Los permisos de Administrador, Comercial y Consulta se verifican desde la interfaz y mediante solicitudes directas.
- Las reglas de cotizaciones, inventario, ventas, correo y reportes conservan los datos correctamente.
- No quedan fallos críticos o altos abiertos.
- Existe una guía suficiente para instalar, operar, respaldar y restaurar el sistema.
- La evidencia académica describe la versión realmente comprobada.
- Los secretos, datos personales y credenciales quedan fuera del repositorio y de las capturas.

## Alcance

### Incluido

- Revisión funcional de autenticación, usuarios, clientes, catálogo e inventario.
- Revisión completa del ciclo de cotizaciones y sus seis estados.
- Verificación de reservas, liberaciones, vencimientos y series entregadas.
- Conversión de cotización aceptada a venta y trazabilidad posterior.
- PDF, correo manual e historial de envíos.
- Panel mensual y reportes filtrables.
- Revisión visual, accesibilidad básica y comportamiento adaptable.
- Pruebas automatizadas y pruebas manuales documentadas.
- Guía de usuario y guía técnica dentro de este mismo documento.
- Procedimiento de respaldo y restauración.
- Registro de validación con usuarios e incidencias.
- Preparación del commit final de la Entrega 7.

### Fuera de alcance

- Pasarela de pago.
- Compras a proveedores.
- Pagos parciales, comprobantes o referencias bancarias.
- Aceptación pública de cotizaciones por el cliente.
- Exportaciones nuevas a Excel o PDF para reportes.
- Funciones nuevas que no sean necesarias para corregir un defecto de cierre.
- Despliegue en producción sin autorización y datos del servidor definitivo.

## Estado inicial de la entrega

Las Entregas 1 a 6 están terminadas. La verificación más reciente registra 83 pruebas y 506 aserciones aprobadas, Laravel Pint aprobado, plantillas Blade compiladas y revisión visual del Panel y reportes en escritorio y móvil.

La Entrega 7 debe comprobar nuevamente el sistema completo después de cualquier corrección. Las cifras de pruebas anteriores sirven como referencia y no sustituyen la evidencia final.

## Orden de trabajo

### Fase 1: preparar un entorno reproducible

- [x] Confirmar rama, commit inicial y distinguir los archivos pendientes del repositorio.
- [x] Verificar versiones de PHP, Composer, Node, npm y MySQL.
- [x] Confirmar que `.env` no está rastreado y que `.env.example` no contiene secretos.
- [x] Verificar dependencias mediante `composer install --dry-run` y `npm ls --depth=0`.
- [x] Crear una base de datos de prueba vacía y ejecutar todas las migraciones.
- [x] Confirmar que la aplicación inicia y permite autenticar un usuario de prueba.
- [x] Registrar cualquier requisito manual que no esté explicado en este documento o en `README.md`.

**Criterio de aceptación:** una instalación nueva puede prepararse sin copiar archivos privados ni modificar el código.

#### Evidencia de la Fase 1

- Rama `main` sincronizada con `origin/main` en el commit `32bebfe` al iniciar la fase.
- Los tres archivos `work-entry*.php` ya existentes siguen sin seguimiento y no forman parte de la entrega.
- Entorno comprobado: PHP 8.2.12, Composer 2.9.5, Node 22.20.0, npm 10.9.3 y servidor MySQL 8.0.42.
- `.env` está ignorado por Git y `.env.example` no contiene contraseñas. El ejemplo se ajustó para declarar MySQL como base del proyecto.
- `composer install --dry-run` no encontró cambios pendientes y `composer check-platform-reqs` aprobó los requisitos declarados por las dependencias.
- `npm ls --depth=0` confirmó las seis dependencias directas instaladas sin faltantes.
- Las 13 migraciones se ejecutaron desde cero en SQLite en memoria y en una base MySQL temporal. En MySQL, las mismas 13 migraciones aparecen aplicadas sobre la base local `comunitec`.
- `tests/Feature/AutenticacionTest.php`: 3 pruebas y 14 aserciones aprobadas, incluidos acceso activo y bloqueo de usuario inactivo.
- `php artisan schedule:list` confirmó la ejecución diaria de `cotizaciones:vencer`.
- `php artisan route:list --except-vendor` registró inicialmente 35 rutas propias; el cierre agrega la ruta protegida para activar o desactivar conceptos del catálogo.

#### Pendientes del entorno encontrados

- [x] Habilitar la extensión PHP `intl`. El usuario confirmó que `php -m` y `php artisan db:show --counts` terminaron correctamente.
- [x] Aclarar el entorno: PHP proviene de `C:\xampp\php`, pero el servidor de base de datos es una instalación independiente de MySQL 8. Laravel se conecta mediante `pdo_mysql`; el cliente MariaDB incluido en XAMPP no forma parte de la operación del sistema.
- [x] Repetir una migración limpia en una base MySQL temporal. Las 13 migraciones se aplicaron en `comunitec_e7_migracion_20260924`; después se eliminó la base temporal y se confirmó que ya no existía. La base real no fue alterada.

### Fase 2: ejecutar las comprobaciones automatizadas

- [x] Ejecutar `composer formato`.
- [x] Ejecutar `php artisan test` y registrar cantidad de pruebas y aserciones.
- [x] Ejecutar `php artisan view:cache`.
- [x] Ejecutar `composer formato:verificar`.
- [x] Ejecutar `php artisan route:list` y revisar protección de rutas.
- [x] Ejecutar `php artisan migrate:status` contra la base MySQL de desarrollo.
- [x] Ejecutar `php artisan schedule:list` y confirmar el vencimiento diario.
- [x] Ejecutar `npm run build` fuera del sandbox si `esbuild` vuelve a quedar bloqueado por permisos.
- [x] Ejecutar `git diff --check` antes del commit final.

**Criterio de aceptación:** todas las comprobaciones aplicables terminan correctamente. Un bloqueo del entorno se registra por separado y se repite en la terminal local del usuario.

#### Evidencia de la Fase 2

- `composer formato`: 83 archivos aprobados.
- `php artisan test`: 83 pruebas y 506 aserciones aprobadas en 14.40 segundos.
- `php artisan view:cache`: plantillas Blade compiladas correctamente.
- `composer formato:verificar`: 83 archivos aprobados.
- `php artisan route:list --except-vendor`: 35 rutas propias registradas.
- `php artisan migrate:status`: 13 migraciones aplicadas en MySQL.
- `php artisan schedule:list`: `cotizaciones:vencer` programado diariamente a las 00:00.
- `npm run build`: Vite 7.3.6 transformó 58 módulos y terminó en 916 ms.
- `git diff --check`: terminó sin errores.
- Verificación posterior a la corrección E7-001: 85 pruebas y 513 aserciones aprobadas en 17.44 segundos; Pint, Blade y `git diff --check` aprobados.
- Verificación final posterior a todos los cambios: 89 pruebas y 559 aserciones aprobadas en 16.95 segundos; Pint, Blade, 36 rutas, programación diaria y `git diff --check` aprobados.
- La compilación final dentro de Codex volvió a quedar bloqueada por el acceso de `esbuild` a un directorio superior, incluso después de conceder lectura a `C:\Users\liyjo`. La compilación local ejecutada por el usuario aprobó Vite 7.3.6 con 58 módulos en 916 ms; después de ella no se modificaron archivos fuente procesados por Vite.

### Fase 3: probar el flujo comercial completo

Usar datos ficticios claramente identificados como pruebas. No usar información personal de clientes reales.

#### Acceso y usuarios

- [x] Iniciar sesión con un usuario activo.
- [x] Comprobar el rechazo de un usuario desactivado.
- [x] Crear, editar, desactivar y reactivar un usuario como Administrador.
- [x] Verificar que Comercial y Consulta no pueden administrar usuarios.

#### Clientes y catálogo

- [x] Registrar y editar una persona física.
- [x] Registrar una persona moral.
- [x] Registrar un producto y un servicio.
- [x] Comprobar que los precios se muestran como importes con IVA incluido.
- [x] Desactivar un artículo y comprobar que no puede agregarse a nuevas cotizaciones.

#### Inventario

- [x] Registrar al menos cinco piezas serializadas de un producto ficticio.
- [x] Intentar repetir un número de serie y comprobar la validación.
- [x] Confirmar la alerta cuando un producto tiene cinco piezas disponibles o menos.
- [x] Verificar los estados Disponible, Reservada y Entregada durante el flujo.

#### Cotización

- [x] Crear un borrador con cliente, área solicitante y descuento permitido.
- [x] Agregar un producto, un servicio y un concepto Otro.
- [x] Comprobar subtotal, descuento y total calculados por el servidor.
- [x] Intentar un descuento inválido y verificar el mensaje sin perder los datos capturados.
- [x] Cotizar más piezas de las disponibles y comprobar la advertencia sin bloquear el borrador.
- [x] Descargar el PDF y compararlo con los datos guardados.
- [x] Enviar manualmente la cotización por correo y revisar el historial del intento.
- [x] Confirmar el cambio de Borrador a Pendiente y la vigencia de 15 días.
- [x] Comprobar el rechazo de una cotización pendiente.
- [x] Comprobar el vencimiento de una cotización pendiente fuera de plazo.

#### Aceptación y reservas

- [x] Bloquear la aceptación cuando no existen piezas suficientes.
- [x] Aceptar una cotización con inventario suficiente y comprobar las reservas.
- [x] Editar una aceptada, confirmar la liberación y exigir una nueva aceptación.
- [x] Cancelar una aceptada y comprobar que las piezas vuelven a estar disponibles.
- [x] Ejecutar el vencimiento de cinco días y comprobar la cancelación y liberación.

#### Venta

- [x] Convertir una cotización aceptada en venta.
- [x] Elegir un método de pago y asignar exactamente las series reservadas.
- [x] Comprobar que las piezas pasan a Entregada sin un segundo descuento de inventario.
- [x] Intentar convertir nuevamente la misma cotización y comprobar el bloqueo.
- [x] Confirmar que la venta conserva cliente, responsable, partidas e importes históricos.
- [x] Consultar desde Inventario la venta y el cliente asociados a una serie entregada.

#### Panel y reportes

- [x] Abrir el Panel del mes actual.
- [x] Comparar los seis conteos de cotizaciones contra los registros mostrados.
- [x] Comparar cantidad e importe de ventas contra las ventas mostradas.
- [x] Filtrar cotizaciones por fechas, cliente y estado.
- [x] Filtrar ventas por fechas, cliente y método de pago.
- [x] Comprobar que la paginación conserva filtros y que los totales incluyen todas las páginas.

**Criterio de aceptación:** el flujo termina en una venta trazable, mantiene las reglas de inventario y no genera operaciones duplicadas o parciales.

#### Evidencia del recorrido principal

- Los casos que no requerían observar una integración externa se comprobaron con pruebas de característica repetibles: usuario inactivo y administración por rol; edición de persona física; artículo desactivado; descuento inválido; advertencia y bloqueo por faltantes; rechazo y vencimientos; liberación al editar, cancelar o vencer; bloqueo de venta duplicada; y paginación con filtros y totales globales.
- Catálogo ahora permite desactivar y reactivar productos o servicios desde la interfaz. Una prueba comprueba ambos cambios de estado y otra confirma que el rol Consulta recibe 403 al intentar la acción directamente.
- La segunda página del reporte de ventas se comprobó con 21 resultados: muestra el registro restante, conserva cliente, responsable y método de pago, y mantiene la cantidad y el importe calculados antes de paginar.
- Se crearon clientes, un producto, un servicio y cinco piezas con identificadores de prueba de Entrega 7.
- La serie duplicada `E7-0003` fue rechazada con un mensaje visible y conservó los datos del formulario.
- La cotización del recorrido guardó producto, servicio y concepto libre: subtotal de $4,000.00, descuento de 5% y total de $3,800.00.
- El servicio SMTP aceptó un envío al buzón de prueba previamente autorizado y el historial registró fecha, destinatario, usuario y resultado.
- La cotización pasó de Borrador a Pendiente y Aceptada; reservó dos piezas.
- La venta se registró por transferencia con las series `E7-0001` y `E7-0002`, ambas visibles como Entregadas y enlazadas a la venta y al cliente.
- El reporte de ventas filtrado devolvió un resultado y un total de $3,800.00.
- El navegador integrado bloqueó la apertura directa del PDF con `ERR_BLOCKED_BY_CLIENT`, por lo que se generó el mismo documento directamente mediante Laravel y DOMPDF para inspeccionarlo fuera del visor.
- El PDF resultante es un A4 de una página. Conserva cliente, RFC, dirección, responsable, área solicitante, tres partidas, subtotal de $4,000.00, descuento de 5% por $200.00, total de $3,800.00 y la indicación de IVA incluido. La revisión renderizada a 150 ppp no mostró texto cortado, elementos superpuestos ni contenido fuera de página.

### Fase 4: revisar permisos

| Módulo o acción | Administrador | Comercial | Consulta |
| --- | --- | --- | --- |
| Panel y reportes | Lectura | Lectura | Lectura |
| Usuarios | Escritura | Sin acceso | Sin acceso |
| Clientes | Escritura | Escritura | Sin escritura |
| Catálogo e inventario | Escritura | Escritura | Sin escritura |
| Cotizaciones | Escritura | Escritura | Lectura |
| PDF de cotización | Lectura | Lectura | Lectura |
| Ventas | Escritura | Escritura | Lectura |

- [x] Comprobar los enlaces visibles para cada rol.
- [x] Probar solicitudes directas a rutas restringidas, aunque el enlace no aparezca.
- [x] Confirmar respuestas 403 o redirecciones correctas.
- [x] Confirmar que Consulta no recibe formularios POST, PUT, PATCH o DELETE.

**Criterio de aceptación:** ocultar un enlace no es la única protección; el servidor impide todas las operaciones no autorizadas.

#### Evidencia de la Fase 4

- Administrador y Comercial recibieron respuesta correcta al abrir Panel, Clientes, Catálogo, Inventario, Cotizaciones, Ventas y ambos reportes.
- Solo Administrador pudo abrir Administración de usuarios; Comercial y Consulta recibieron 403.
- Consulta pudo abrir Panel, Cotizaciones, Ventas y ambos reportes.
- Consulta recibió 403 al intentar abrir Clientes, Catálogo, Inventario y Usuarios.
- Solicitudes directas de Consulta para crear o actualizar clientes, crear catálogo, registrar categorías o series, convertir ventas y crear usuarios recibieron 403 con recursos existentes cuando correspondía.
- Las vistas de lectura de cotizaciones para Consulta no mostraron formularios de escritura en ninguno de los seis estados.
- Pruebas dirigidas de permisos y navegación: 10 aprobadas y 149 aserciones.
- Suite completa posterior a la matriz de permisos: 88 pruebas y 545 aserciones aprobadas en 15.13 segundos; el cierre final amplió esa cifra a 89 pruebas y 559 aserciones.

### Fase 5: revisar experiencia de uso

Revisar las pantallas principales a 1280 × 800 y 390 × 844.

- [x] Comprobar que no existe desbordamiento horizontal del documento.
- [x] Comprobar que las tablas anchas tienen desplazamiento dentro de su contenedor.
- [x] Recorrer controles y enlaces mediante teclado.
- [x] Verificar foco visible y orden lógico.
- [x] Confirmar etiquetas asociadas a campos.
- [x] Revisar contraste, tamaño de controles y mensajes de error.
- [x] Revisar estados vacíos y mensajes de confirmación.
- [x] Revisar la consola del navegador y solicitudes fallidas.
- [x] Confirmar que todas las pantallas y mensajes propios están en español.

**Criterio de aceptación:** todas las tareas principales se completan en escritorio y móvil sin perder contenido ni depender del ratón.

#### Evidencia de la Fase 5

- Se revisaron Panel, Clientes, Catálogo, Inventario, Cotizaciones, detalle de cotización, Ventas, detalle de venta y ambos reportes a 1280 × 800 y 390 × 844.
- En escritorio, el ancho de documento fue igual o menor al ancho de ventana en las diez rutas.
- En móvil, el ancho de documento fue igual o menor a 390 px en las diez rutas.
- Las tablas anchas de Catálogo y detalle de Venta conservaron el desplazamiento dentro de `.contenedor-tabla` con `overflow-x: auto`.
- El primer enlace recorrido por teclado mostró un contorno azul sólido de 2.4 px; el orden inició en Panel y continuó con la navegación principal.
- Los filtros del reporte se apilaron en una columna y conservaron etiquetas, controles legibles y botones accesibles en móvil.
- La consola no registró errores ni advertencias durante la revisión.
- El tamaño temporal del navegador se restauró al terminar.

### Fase 6: registrar y resolver incidencias

Cada incidencia se registrará en la siguiente tabla antes de corregirse:

| ID | Fecha | Módulo | Pasos para reproducir | Resultado actual | Resultado esperado | Severidad | Estado | Evidencia |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| E7-001 | 25/09/2026 | Catálogo | Registrar un servicio dejando vacías las existencias | El servidor rechazaba el campo vacío como no entero | Los servicios aceptan el campo vacío; los productos exigen un entero | Media | Resuelta | Prueba `test_un_servicio_puede_crearse_sin_existencias` RED→GREEN |

Severidades:

- **Crítica:** pérdida de datos, acceso indebido, inventario incorrecto o imposibilidad de usar el sistema.
- **Alta:** rompe un flujo principal sin alternativa razonable.
- **Media:** el flujo funciona con dificultad o presenta información incorrecta no destructiva.
- **Baja:** detalle visual, texto o mejora sin impacto funcional relevante.

Reglas de cierre:

- [x] Toda corrección funcional incluye una prueba que reproduce el defecto.
- [x] Después de la corrección se ejecutaron sus pruebas dirigidas.
- [x] Al terminar la corrección se repitió la suite completa.
- [x] No existen incidencias críticas o altas abiertas.
- [x] La única incidencia media encontrada se corrigió y verificó; no hay incidencias aplazadas registradas.

### Fase 7: validar con usuarios

Realizar la validación con al menos un representante autorizado de la empresa o, si todavía no está disponible, conservar esta sección como pendiente sin inventar resultados.

#### Datos de la sesión

- Fecha:
- Lugar o medio:
- Versión o commit evaluado:
- Participante y función:
- Responsable de la prueba:

#### Tareas solicitadas

1. Iniciar sesión y localizar un cliente.
2. Crear una cotización con producto, servicio y concepto Otro.
3. Generar el PDF y explicar el cambio a Pendiente.
4. Aceptar la cotización y verificar las piezas reservadas.
5. Convertirla en venta y asignar series.
6. Consultar la venta desde Inventario.
7. Abrir el Panel y aplicar filtros en ambos reportes.

#### Registro de resultados

| Tarea | Completada sin ayuda | Completada con ayuda | No completada | Observaciones |
| --- | --- | --- | --- | --- |
| Inicio y cliente |  |  |  |  |
| Cotización |  |  |  |  |
| PDF y envío |  |  |  |  |
| Aceptación |  |  |  |  |
| Venta |  |  |  |  |
| Trazabilidad |  |  |  |  |
| Panel y reportes |  |  |  |  |

#### Aceptación del usuario

- Aspectos claros:
- Aspectos confusos:
- Cambios solicitados:
- Incidencias encontradas:
- Resultado: Aceptado / Aceptado con observaciones / Requiere correcciones.

**Criterio de aceptación:** los resultados provienen de una sesión real, tienen fecha y versión, y las observaciones se convierten en incidencias trazables.

## Guía de usuario

### Administrador

1. Inicia sesión con correo y contraseña.
2. Administra usuarios desde el módulo Usuarios.
3. Puede realizar las mismas operaciones comerciales que el rol Comercial.
4. Consulta indicadores y reportes desde Panel y Reportes.
5. Desactiva cuentas que ya no deben ingresar; no elimina su historial.

### Comercial

1. Registra o selecciona el cliente.
2. Mantiene categorías, productos, servicios y piezas de inventario.
3. Crea una cotización en Borrador y agrega sus partidas.
4. Descarga el PDF y envía manualmente el correo para pasarla a Pendiente.
5. Registra la respuesta del cliente mediante Aceptar o Rechazar.
6. Si acepta, verifica las piezas reservadas.
7. Convierte la aceptada en venta, registra el método de pago y asigna las series.
8. Consulta la venta y su trazabilidad desde Inventario, Panel y Reportes.

### Consulta

1. Inicia sesión con una cuenta activa.
2. Consulta cotizaciones, PDF y ventas.
3. Consulta Panel y Reportes.
4. No puede crear, editar, eliminar, enviar, cambiar estados o convertir ventas.

### Significado de los estados de cotización

- **Borrador:** puede editarse; todavía no tiene vigencia activa.
- **Pendiente:** fue enviada y espera respuesta; vence a los 15 días.
- **Aceptada:** el Comercial registró la aceptación y las piezas quedaron reservadas.
- **Rechazada:** el cliente no aceptó la propuesta.
- **Cancelada:** se anuló una aceptada o terminó el plazo de cinco días sin venta.
- **Vencida:** una pendiente superó su vigencia sin aceptación.

### Reglas que el usuario debe conocer

- Los precios ya incluyen IVA.
- El descuento puede ser cero o estar entre 5% y 10%.
- El sistema permite guardar un borrador con faltantes, pero no aceptarlo.
- Modificar una aceptada libera sus reservas y exige aceptarla nuevamente.
- La venta es una acción separada; aceptar no significa que se haya pagado.
- El sistema registra el método de pago, pero no procesa pagos.
- La aceptación del servidor de correo no demuestra que el cliente leyó el mensaje.

## Guía técnica

### Requisitos

- PHP 8.2 o superior con extensiones requeridas por Laravel.
- Extensión PHP `intl` para los comandos de inspección y formato numérico de Laravel.
- Composer.
- Node.js y npm.
- MySQL.
- Servidor web compatible o `php artisan serve` para desarrollo.
- Cuenta SMTP autorizada si se enviarán correos reales.

### Instalación

```bash
composer install
npm ci
copy .env.example .env
php artisan key:generate
php artisan migrate
npm run build
php artisan serve
```

En Linux o macOS se usa `cp .env.example .env` en lugar de `copy`.

Antes de migrar se configuran en `.env` la base de datos, la URL de la aplicación y el transporte de correo. Las contraseñas nunca se copian a este documento ni se agregan a Git.

### Operación programada

El comando principal para vencimientos es:

```bash
php artisan cotizaciones:vencer
```

El servidor debe ejecutar el programador de Laravel de forma continua o cada minuto. Verificar la programación con:

```bash
php artisan schedule:list
```

### Mantenimiento

- Ejecutar migraciones antes de habilitar una versión nueva.
- Limpiar y regenerar cachés cuando cambie la configuración.
- Revisar `storage/logs` ante errores, evitando publicar datos sensibles.
- Probar restauraciones de respaldo en una base separada.
- Mantener dependencias mediante cambios controlados y pruebas completas.
- No editar manualmente estados, reservas o series en la base de datos durante la operación normal.

### Comprobación posterior a una instalación

```bash
php artisan migrate:status
php artisan route:list
php artisan schedule:list
php artisan test
php artisan view:cache
```

## Respaldo y restauración

### Contenido que debe respaldarse

- Base de datos MySQL.
- Archivo `.env`, almacenado en un medio privado y protegido.
- Archivos persistentes generados por la aplicación, si existen en `storage/app`.
- Identificación del commit o versión del código.

No es necesario respaldar `vendor`, `node_modules` ni `public/build` si pueden reconstruirse desde la versión y los archivos de bloqueo.

### Crear respaldo de MySQL

Ejecutar desde una terminal segura y permitir que `mysqldump` solicite la contraseña, evitando escribirla en el comando:

```bash
mysqldump -h 127.0.0.1 -P 3307 -u USUARIO -p --single-transaction --routines --triggers NOMBRE_BASE > comunitec-respaldo.sql
```

Guardar junto al archivo la fecha, el entorno, la versión del sistema y el responsable. El respaldo puede contener datos personales y requiere acceso restringido.

### Restaurar en una base separada

1. Crear una base vacía destinada a la prueba de restauración.
2. Importar el respaldo:

```bash
mysql -h 127.0.0.1 -P 3307 -u USUARIO -p NOMBRE_BASE_PRUEBA < comunitec-respaldo.sql
```

3. Configurar temporalmente la aplicación para esa base.
4. Ejecutar `php artisan migrate:status`.
5. Iniciar sesión y comprobar clientes, cotizaciones, ventas y series.
6. Comparar conteos principales con el origen.
7. Eliminar de forma segura la base temporal cuando ya no sea necesaria.

**Criterio de aceptación:** el procedimiento se prueba en una base distinta y recupera relaciones, históricos y trazabilidad sin alterar el entorno original.

### Evidencia de respaldo y restauración

- Se generó un respaldo lógico mediante `mysqldump` 8.0.42 con transacción consistente, rutinas y disparadores.
- El archivo generado tuvo 31,355 bytes y se restauró en la base aislada `comunitec_e7_restore_20260925`.
- Los conteos de origen y restauración coincidieron: 3 clientes, 5 cotizaciones, 2 ventas y 9 piezas serializadas.
- La base temporal se eliminó después de la comparación y la base `comunitec` no fue modificada por la restauración.
- El archivo temporal `storage/app/entrega7-verificacion.sql` está excluido por `.gitignore`. El sandbox impidió borrarlo automáticamente; debe eliminarse manualmente al cerrar la validación.

## Evidencia académica

### Evidencia técnica

- [x] Captura o salida de la suite completa aprobada.
- [x] Evidencia de migraciones aplicadas.
- [x] Evidencia del programador de vencimientos.
- [x] Evidencia de compilación frontend.
- [x] Matriz final de permisos.
- [x] Resultado de restauración de un respaldo de prueba.
- [x] Commit final preparado en `main`; repositorio: <https://github.com/liyjoseadri-stack/comunitec>. La subida queda pendiente desde una terminal con credenciales de GitHub disponibles.

### Evidencia funcional

- [x] Inicio de sesión.
- [x] Administración de usuarios.
- [x] Cliente físico y moral.
- [x] Catálogo e inventario serializado.
- [x] Cotización con tres tipos de partida.
- [x] PDF e historial de correo.
- [x] Estados y reservas.
- [x] Venta y series entregadas.
- [x] Panel mensual.
- [x] Reportes filtrados.

### Documentación académica

- [x] Actualizar descripción del problema y objetivos con el alcance final.
- [x] Describir metodología y entregas realizadas.
- [x] Incorporar modelo de datos y arquitectura actualizados.
- [x] Documentar pruebas, resultados y validación con usuarios.
- [x] Presentar limitaciones reales y trabajo futuro.
- [x] Revisar que nombres, cifras, capturas y fechas sean consistentes.
- [x] Evitar incluir contraseñas, correos privados innecesarios o datos reales de clientes.

### Síntesis para el reporte académico

El sistema sustituye el seguimiento disperso en archivos de Excel y Word por un flujo administrativo integrado. La versión cerrada autentica a tres roles, conserva clientes físicos y morales, administra productos y servicios, controla piezas por número de serie, elabora cotizaciones con precios históricos, envía el PDF por correo, registra la respuesta comercial, reserva existencias y convierte una cotización aceptada en una venta trazable. El objetivo alcanzado es reducir recapturas y mantener una relación comprobable entre cliente, cotización, inventario y venta.

El desarrollo se organizó en siete entregas incrementales: entorno y acceso; clientes, catálogo e inventario; elaboración de cotizaciones; estados, PDF y correo; ventas y series; panel y reportes; y cierre integral. Cada entrega combinó implementación, pruebas de característica, revisión visual y un commit identificable. La verificación final suma 89 pruebas y 559 aserciones, además de migración limpia, compilación de vistas, formato, rutas, programación, PDF renderizado, flujo manual y restauración de respaldo.

La arquitectura final usa Laravel 12 con MVC, Blade y Eloquent sobre MySQL. Las entidades principales son usuarios, clientes, categorías, artículos de catálogo, piezas de inventario, cotizaciones, partidas de cotización, envíos de correo, ventas, partidas de venta y series entregadas. Las ventas conservan copias históricas del cliente y responsable; las partidas conservan descripción y precio, y las piezas enlazan reserva, venta y cliente. Los middleware aplican autenticación y roles en el servidor.

La validación técnica y el recorrido funcional con datos ficticios están documentados en este archivo. La sesión de aceptación con un representante de COMUN&TEC permanece como actividad externa; no se inventaron participante, fecha ni resultado. Cuando se realice, se llenará la tabla de la Fase 7 y cada observación se registrará como incidencia antes de corregirse.

Las limitaciones actuales son deliberadas: no existe pasarela de pago, aceptación pública del cliente, pagos parciales, compras a proveedores ni ventas directas. El correo confirma aceptación por el servidor SMTP, no lectura del destinatario. El programador de Laravel y los respaldos requieren configuración operativa en el equipo donde se instale. Como trabajo futuro pueden evaluarse un portal del cliente, notificaciones, compras a proveedores y despliegue remoto, siempre mediante una nueva definición de alcance.

## Revisión de seguridad y privacidad

- [x] Buscar secretos en archivos rastreados y en el historial reciente.
- [x] Confirmar que `.env`, respaldos SQL y registros con datos reales están ignorados.
- [x] Verificar validación de entradas y escape de salida en las pantallas revisadas.
- [x] Verificar protección CSRF en formularios de escritura.
- [x] Confirmar que las contraseñas se almacenan con hash.
- [x] Revisar que los errores no muestren credenciales o detalles internos al usuario.
- [x] Confirmar que las capturas académicas usan datos ficticios o anonimizados.

### Evidencia de seguridad

- La búsqueda de credenciales solo encontró referencias a variables de entorno en `config/database.php` y `config/mail.php`; no encontró contraseñas ni llaves rastreadas.
- `.env` y `storage/app/entrega7-verificacion.sql` están excluidos por reglas de Git.
- Todos los formularios de escritura revisados incluyen `@csrf`; los formularios sin token son consultas GET.
- El modelo `Usuario` aplica el cast `hashed` a la contraseña y la fábrica utiliza `Hash::make`.
- Las vistas Blade mantienen salida escapada para los datos operativos revisados.
- `composer audit --locked` no encontró avisos de vulnerabilidad.
- `npm audit --omit=dev --audit-level=high` reportó cero vulnerabilidades.

## Criterios para cerrar la Entrega 7

- [x] Suite completa aprobada con cifras registradas.
- [x] Migraciones, rutas, Blade, formato y programación verificados.
- [x] Compilación frontend comprobada en un entorno con permisos suficientes.
- [x] Flujo comercial completo probado con datos ficticios.
- [x] Matriz de permisos validada.
- [x] Revisión visual terminada en escritorio y móvil.
- [x] Respaldo y restauración probados en una base separada.
- [x] Validación con usuarios registrada o identificada expresamente como pendiente externo.
- [x] Cero incidencias críticas o altas abiertas.
- [x] Documentación académica actualizada con evidencia real.
- [x] Revisión integral del diff terminada.
- [ ] Un único commit de cierre creado en español y subido a `origin/main`. El commit está creado; falta ejecutar el `push` desde la terminal autenticada del usuario.

## Registro final

Completar esta sección al cerrar la entrega:

- Fecha de cierre: 24 de septiembre de 2026.
- Commit final: commit de cierre de Entrega 7 en `main`, con mensaje en español; el identificador se consulta en el historial porque un commit no puede contener su propio hash.
- Pruebas aprobadas: 89.
- Aserciones aprobadas: 559.
- Migraciones verificadas: 13 en la base de desarrollo y 13 desde cero en una base MySQL temporal.
- Compilación frontend: Vite 7.3.6, 58 módulos, 916 ms en la terminal local del usuario; el reintento en Codex quedó bloqueado por permisos de `esbuild` sobre la ruta superior.
- Navegadores y tamaños revisados: navegador integrado a 1280 × 800 y 390 × 844; diez rutas principales y la actualización de Catálogo.
- Validación con usuarios: pendiente de una sesión externa con un representante autorizado de COMUN&TEC; formato de registro preparado en la Fase 7.
- Resultado de respaldo y restauración: conteos coincidentes de 3 clientes, 5 cotizaciones, 2 ventas y 9 piezas; base temporal eliminada.
- Incidencias aplazadas: ninguna incidencia técnica crítica, alta o media; permanece la validación externa.
- Observaciones: el archivo temporal de respaldo permanece ignorado y requiere eliminación manual debido a la restricción del sandbox.
