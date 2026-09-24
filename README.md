# Comunitec

Sistema administrativo de clientes, catálogo, inventario y cotizaciones para COMUN&TEC, desarrollado con Laravel 12, PHP 8.2 o superior y MySQL.

## Instalación local

1. Ejecutar `composer install` y `npm ci`.
2. Copiar `.env.example` a `.env` si no existe y configurar la conexión a una base de desarrollo.
3. Ejecutar `php artisan key:generate` solo en una instalación nueva.
4. Ejecutar `php artisan migrate` y `npm run build`.
5. Iniciar con `php artisan serve` y abrir `/iniciar-sesion`.

No guardar contraseñas en Git. Los usuarios se administran desde el módulo protegido de administración.

## Comprobaciones

- `composer formato`: aplica el formato de PHP con Laravel Pint.
- `composer formato:verificar`: verifica el formato sin modificar archivos.
- Las vistas Blade deben mantener etiquetas, bloques y directivas con sangría de cuatro espacios. No comprimir plantillas ni clases en una sola línea.

- `php artisan test`: pruebas de autenticación, permisos, clientes, inventario y cotizaciones con SQLite en memoria.
- `php artisan view:cache`: compilación de plantillas.
- `npm run build`: compilación de estilos y JavaScript.

## Reglas comerciales

Las ventas se originan en una cotización aceptada y su conversión es manual. No hay pasarela de pago. Los precios incluyen IVA. Una cotización pendiente vence a los 15 días y una aceptada reserva piezas por 5 días.

Modificar partidas de una aceptada libera sus reservas y la devuelve a Pendiente; debe aceptarse nuevamente. Los cambios inválidos conservan las reservas. Cancelar libera piezas. El comando `quotes:expire` procesa los vencimientos; requiere ejecutar el programador de Laravel para funcionar automáticamente.

## Idioma y compatibilidad

Los modelos, controladores, servicios, correos, vistas y pruebas propios usan nombres en español. Laravel usa `APP_LOCALE=es` y las traducciones están en `lang/es`.

Se conservan nombres técnicos de Laravel, Composer y PHPUnit (`app`, `config`, `routes`, `tests`, `README.md`, `AGENTS.md`, `composer.json`, `package.json`, `artisan`) y los métodos que sus interfaces requieren. Las tablas, columnas y nombres históricos de migraciones se conservan para abrir la base existente sin perder datos ni repetir migraciones. Las bibliotecas instaladas en `vendor` y `node_modules` conservan su código original.

## Estado y pendientes

El plan está en `tasks/plan.md` y el seguimiento en `tasks/todo.md`. La Entrega 3 de creación y cálculo de cotizaciones está terminada. El SMTP real ya fue configurado y comprobado con una cuenta autorizada; sus credenciales permanecen únicamente en `.env`. Los estados y reservas tienen avances de entregas posteriores. La conversión a venta y la selección de series entregadas corresponden a la Entrega 5.
