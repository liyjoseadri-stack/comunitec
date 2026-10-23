# Configuración segura de producción

Esta guía describe la configuración de la versión actual de Comunitec. Los comandos son instrucciones para el responsable del despliegue; deben ejecutarse desde la raíz de la aplicación, durante una ventana acordada y después de probar el procedimiento en un entorno separado. No contienen datos de una instalación real.

## Entorno y valores requeridos

`.env.example` es una plantilla de desarrollo: conserva `APP_ENV=local`, `APP_DEBUG=true` y `SESSION_SECURE_COOKIE=false`. Crear el `.env` de producción fuera del control de versiones y proporcionar estos valores mediante el mecanismo privado del servidor:

| Variable | Requisito en producción |
| --- | --- |
| `APP_ENV` | `production`. |
| `APP_DEBUG` | `false`; no exponer trazas ni configuración en respuestas HTTP. |
| `APP_URL` | URL pública completa con `https://`, correspondiente a la instalación. |
| `SCHEDULE_TIMEZONE` | `America/Mexico_City` para ejecutar los horarios comerciales en la hora local de Chiapas. Los datos de la aplicación permanecen en UTC. |
| `APP_KEY` | Clave privada persistente de la instalación; generarla una sola vez con `php artisan key:generate` en una instalación nueva. Conservarla al actualizar y al restaurar. |
| `APP_LOCALE`, `APP_FALLBACK_LOCALE` | `es`. |
| `DB_CONNECTION` | `mysql`. |
| `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Datos privados de una base dedicada; cuenta con los permisos necesarios, sin usar la cuenta raíz del servidor. |
| `SESSION_DRIVER`, `SESSION_LIFETIME` | `file` y `120` para una única instancia, según la configuración actual. |
| `SESSION_SECURE_COOKIE` | `true`, exclusivamente detrás de HTTPS en producción. |
| `SESSION_HTTP_ONLY`, `SESSION_SAME_SITE` | `true` y `lax`; conservar las protecciones de la cookie. |
| `SESSION_PATH`, `SESSION_DOMAIN` | `/` y `null`, salvo que la topología requiera expresamente otro alcance. |
| `CACHE_STORE` | `file` para una única instancia; almacena también el contador de intentos de acceso y los bloqueos del scheduler. |
| `LOG_CHANNEL`, `LOG_STACK`, `LOG_LEVEL`, `LOG_DAILY_DAYS` | `stack`, `daily`, `warning` y `14` como base de operación con rotación local; ajustar retención a las necesidades de soporte. |
| `QUEUE_CONNECTION` | `sync`, como en la versión actual; el correo se procesa en la solicitud o en el comando programado. |
| `MAIL_MAILER` | `smtp`, con transporte y remitente autorizados. |

No sustituir una `APP_KEY` existente al desplegar: afectaría sesiones y datos cifrados. Guardar una copia privada recuperable junto con la configuración necesaria para restauración. Las variables `USUARIO_ADMIN_*` solo se usan para el alta inicial mediante el seeder; si se utiliza, suministrarlas de forma privada y retirar la contraseña de aprovisionamiento después. No ejecutar seeders rutinariamente sobre una base con operaciones.

El almacenamiento de sesiones y caché debe ser persistente y accesible por los procesos web y programados. Para varias instancias, resolver primero el almacenamiento compartido de sesiones, caché y bloqueos; los archivos locales independientes no comparten el límite de intentos. Esta guía no cambia la arquitectura actual de una instancia.

## HTTPS y archivos del servidor

- Configurar un certificado válido y redirección de HTTP a HTTPS. Verificar acceso y cierre de sesión desde el navegador con la cookie marcada `Secure`, `HttpOnly` y `SameSite=Lax` antes de habilitar usuarios.
- La aplicación agrega `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy` y una política CSP acotada para formularios, marcos, objetos y URL base. Sobre una solicitud HTTPS también agrega HSTS. Comprobar estos encabezados después de configurar el proxy y evitar que el servidor web los elimine o contradiga.
- Si un proxy termina TLS, configurar únicamente los proxies de confianza de la instalación y comprobar que Laravel reconoce HTTPS y la IP del cliente. Esa IP forma parte de la clave del límite de acceso; no confiar indiscriminadamente en cabeceras enviadas por clientes.
- Publicar únicamente el directorio `public/` como raíz web. `.env`, respaldos, registros, `vendor/` y el código fuera de `public/` no deben quedar descargables.
- Dar al proceso de PHP lectura del código y escritura solo donde se necesita: `storage/` y `bootstrap/cache/`. Proteger `.env` y las cachés de configuración, que pueden contener secretos. No conceder escritura general al código ni permisos globales de lectura/escritura.
- Restringir el acceso a la base y a los registros al personal y procesos autorizados. No registrar contraseñas, tokens ni el contenido de `.env`; revisar espacio disponible y rotación de registros.

## Actualización y cachés

Preparar una versión identificada del código y sus dependencias bloqueadas, ejecutar las pruebas en un entorno separado y respaldar antes de actualizar. Con el servidor en mantenimiento y el disparador del scheduler pausado durante la actualización:

```console
php artisan down
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
npm ci
npm run build
php artisan config:clear
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan route:list --except-vendor
php artisan schedule:list
php artisan up
```

Los recursos pueden compilarse en el entorno de construcción y distribuirse junto con la versión. No usar `composer setup` para actualizar producción: automatiza tareas de instalación inicial, incluida la generación de clave. Tras modificar `.env`, regenerar la caché de configuración; los procesos que siguen vivos deben reiniciarse mediante el gestor del servidor. Si un comando falla, mantener el mantenimiento hasta corregirlo o aplicar la reversión. Reanudar el scheduler al terminar y revisar el acceso y las páginas principales.

## Scheduler y correo

Configurar un único disparador del sistema operativo que ejecute cada minuto, con la ruta absoluta de PHP y la carpeta correcta de la aplicación:

```console
php artisan schedule:run
```

En Windows puede utilizarse el Programador de tareas; en otros servidores, su programador equivalente. Usar una cuenta con acceso a configuración, base, caché y registros. Conservar la salida de errores en una ubicación privada y comprobar que el disparador realmente se ejecuta. `schedule:list` solo comprueba la programación, no ejecuta los trabajos.

Actualmente `routes/console.php` programa `cotizaciones:recordar-vencimiento` a las 08:00 y `cotizaciones:vencer` a las 08:10, ambos con `withoutOverlapping()` y la zona `SCHEDULE_TIMEZONE`. El valor predeterminado es `America/Mexico_City`, por lo que se ejecutan a esas horas locales; las fechas persistidas y la zona general de Laravel continúan en UTC. `schedule:list` puede representar la expresión convertida a UTC, por ejemplo 14:00 UTC cuando Chiapas está en UTC-6. El primer trabajo puede enviar correos y el segundo cambia estados y libera inventario; no ejecutar `schedule:run` como una simple comprobación en una base operativa. No borrar la caché de bloqueos mientras los comandos estén ejecutándose.

### Política de idempotencia y recuperación de correo

Cada recordatorio automático se reserva en la base antes de contactar SMTP. Los resultados `pendiente`, `aceptada` y `fallido` conservan la misma clave única y no se reintentan automáticamente. Una excepción del transporte puede ocurrir después de que el servidor haya recibido el mensaje; repetirla sin confirmar el resultado podría enviar dos correos. Un registro `pendiente` antiguo indica que el proceso se interrumpió o no pudo guardar la confirmación después del contacto.

El envío manual aplica la misma separación: reserva el intento en una transacción corta, termina la transacción, contacta SMTP y después confirma el resultado en otra transacción. Cada formulario contiene un token UUID; el doble clic o la repetición de esa misma solicitud recupera el registro existente y no repite SMTP. Volver a abrir el detalle crea un token nuevo y representa un reintento intencional.

Ante un resultado `pendiente`, revisar primero el registro privado de la aplicación y, cuando sea posible, el historial del proveedor SMTP. Significa que el resultado es incierto; no borrar la fila ni volver a utilizar su clave. Si se confirma que el proveedor no recibió el mensaje y todavía es comercialmente oportuno, abrir nuevamente la cotización para crear un intento manual nuevo. Un resultado `fallido` significa que el transporte confirmó el rechazo: la cotización conserva estado y fechas, y puede intentarse otra vez desde un formulario nuevo. Las claves automáticas nunca se borran ni reutilizan. Si el resultado no puede confirmarse, conservar el registro y no reenviar.

Configurar de forma privada `MAIL_HOST`, `MAIL_PORT`, `MAIL_SCHEME`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` y `MAIL_TIMEOUT` según el proveedor autorizado. `MAIL_TIMEOUT` limita en segundos la espera de la conexión SMTP y vale 30 de forma predeterminada; ajustarlo solo con evidencia del proveedor y reiniciar los procesos después de reconstruir la caché. Por ejemplo, `smtps` corresponde a TLS implícito; para STARTTLS utilizar los valores exigidos por el proveedor y comprobar la negociación. Mantener verificación de certificados. `MAIL_MAILER=log` sirve para desarrollo y está bloqueado como envío real de cotizaciones.

Comprobar SMTP primero en un entorno separado, con cotización ficticia y destinatario autorizado: verificar recepción y apertura del PDF, además de la aceptación del transporte. No enviar datos comerciales reales como prueba. Revisar después el historial de envíos y los errores privados; la aceptación SMTP no demuestra lectura del correo.

## Respaldos y recuperación

- Mantener respaldos de MySQL y de los archivos persistentes de `storage/` que la instalación necesite, más la versión exacta del código y sus archivos de bloqueo. Guardar `.env` y `APP_KEY` mediante un canal privado cifrado, separado del repositorio.
- Definir frecuencia, retención, responsable y ubicación externa al servidor. Comprobar espacio, cifrado y permisos; un respaldo no debe alojarse dentro de `public/`.
- Crear un respaldo consistente con la herramienta de MySQL antes de migrar. Proporcionar credenciales mediante un archivo privado o el gestor de secretos, nunca en la línea de comandos ni en documentos versionados. Si es necesario coordinar archivos y base, detener escrituras y pausar trabajos durante la copia.
- Probar periódicamente la restauración en una base aislada, con correo bloqueado y scheduler detenido. Comprobar acceso de un administrador activo, cotizaciones, partidas, ventas, inventario y archivos. Registrar fecha, resultado y tiempo de recuperación sin copiar datos personales.

## Reversión

1. Mantener o activar `php artisan down` y pausar el disparador del scheduler. Conservar evidencia privada del error y cualquier dato nuevo antes de sobrescribir la instalación.
2. El responsable técnico registrado para la ventana restaura la versión anterior identificada del código y sus archivos `composer.lock` y `package-lock.json`. La Etapa 7 agrega tres migraciones: idempotencia de correo e índice de recordatorios (`2026_10_16_000001`), índices de consultas (`2026_10_16_000002`) y movimientos de existencias (`2026_10_16_000003`). Son cambios aditivos y el código anterior puede operar conservándolos; por defecto no ejecutar rollback durante una reversión de emergencia. Si se decide retirar el esquema, hacerlo después de respaldar y revisar si existen claves de envío o movimientos que deban conservarse. Registrar la fecha y hora de inicio de la reversión.
3. Si otra actualización modificó el esquema o los datos, evaluar su compatibilidad antes de volver al código anterior. Restaurar el respaldo validado de base y archivos solo con una decisión operativa explícita: puede perder cambios posteriores al respaldo. No usar `migrate:fresh`, `db:wipe` ni un rollback de migraciones como recuperación genérica.
4. Recuperar la configuración privada y la misma `APP_KEY`; limpiar y reconstruir las cachés para la versión restaurada:

```console
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan route:list --except-vendor
php artisan schedule:list
php artisan up
```

5. Verificar acceso, reportes y operaciones principales; reanudar el scheduler y supervisar errores. Si la comprobación falla, conservar el mantenimiento y resolver el incidente.

### Responsables y fechas de control

No desplegar ni revertir sin completar este registro operativo con personas y fechas reales:

| Control | Responsable | Fecha o frecuencia |
|---|---|---|
| Revisar actualizaciones y avisos de seguridad de Composer y NPM | Responsable técnico designado | Primer día hábil de cada mes y antes de cada despliegue |
| Ejecutar pruebas completas con los archivos de bloqueo vigentes | Responsable técnico de la versión | Fecha de la ventana de despliegue, antes de publicar |
| Validar SMTP, `MAIL_TIMEOUT` y un destinatario ficticio autorizado | Responsable de infraestructura | Fecha de la ventana, antes de reactivar el scheduler |
| Autorizar y ejecutar una reversión | Responsable operativo designado y responsable técnico | En la fecha y hora registradas del incidente |
| Confirmar acceso, cotizaciones, correo, ventas y reportes tras despliegue o reversión | Responsable funcional de COMUN&TEC | Inmediatamente después de la operación |

La bitácora debe anotar nombre, fecha, hora, versión, resultado y evidencia privada. Los cargos anteriores no sustituyen la asignación nominal antes de cada ventana.

## Lista de comprobación operativa

- [ ] Configuración privada revisada: producción, debug desactivado, clave conservada, MySQL dedicado y registros protegidos.
- [ ] HTTPS, cookie de sesión, raíz `public/`, permisos e IP reconocida por Laravel verificados.
- [ ] Existe al menos un administrador activo y se comprobaron permisos de los tres roles.
- [ ] Pruebas y compilación de la versión aprobadas antes de desplegar; cachés reconstruidas sin errores.
- [ ] Disparador único cada minuto, horarios UTC y almacenamiento persistente comprobados.
- [ ] SMTP y PDF comprobados con datos ficticios y destinatario autorizado.
- [ ] Respaldo consistente disponible, restauración ensayada y versión anterior identificada.
- [ ] Acceso y flujos principales comprobados; scheduler reanudado y registros supervisados tras la actualización.
