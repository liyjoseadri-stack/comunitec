<?php

use App\Http\Controllers\Administracion\ControladorUsuarios;
use App\Http\Controllers\Auth\ControladorSesion;
use App\Http\Controllers\ControladorCategorias;
use App\Http\Controllers\ControladorClientes;
use App\Http\Controllers\ControladorCotizaciones;
use App\Http\Controllers\ControladorPanel;
use App\Http\Controllers\ControladorPartidas;
use App\Http\Controllers\ControladorPerfil;
use App\Http\Controllers\ControladorProductos;
use App\Http\Controllers\ControladorReportes;
use App\Http\Controllers\ControladorServicios;
use App\Http\Controllers\ControladorVentas;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('panel');
});

Route::middleware('guest')->group(function () {
    Route::get('/iniciar-sesion', [
        ControladorSesion::class,
        'formulario',
    ])->name('login');
    Route::post('/iniciar-sesion', [
        ControladorSesion::class,
        'guardar',
    ])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/panel', [
        ControladorPanel::class,
        'mostrar',
    ])->name('panel');
    Route::post('/cerrar-sesion', [
        ControladorSesion::class,
        'eliminar',
    ])->name('logout');
    Route::get('/mi-perfil', [
        ControladorPerfil::class,
        'mostrar',
    ])->name('perfil.mostrar');
    Route::put('/mi-perfil', [
        ControladorPerfil::class,
        'actualizar',
    ])->name('perfil.actualizar');
    Route::put('/mi-perfil/contrasena', [
        ControladorPerfil::class,
        'actualizarContrasena',
    ])->name('perfil.contrasena');
    Route::middleware('rol:administrador,comercial,consulta')->group(function () {
        Route::get('/reportes', [
            ControladorReportes::class,
            'resumen',
        ])->name('reportes.resumen');
        Route::get('/cotizaciones', [
            ControladorCotizaciones::class,
            'listar',
        ])->name('cotizaciones.listado');
        Route::get('/cotizaciones/{cotizacion}', [
            ControladorCotizaciones::class,
            'mostrar',
        ])->name('cotizaciones.detalle');
        Route::get('/cotizaciones/{cotizacion}/pdf', [
            ControladorCotizaciones::class,
            'pdf',
        ])->name('cotizaciones.pdf');
        Route::get('/ventas', [
            ControladorVentas::class,
            'listar',
        ])->name('ventas.listado');
        Route::get('/ventas/{venta}/pdf', [
            ControladorVentas::class,
            'pdf',
        ])->name('ventas.pdf');
        Route::get('/ventas/{venta}', [
            ControladorVentas::class,
            'mostrar',
        ])->name('ventas.detalle');
        Route::get('/reportes/cotizaciones', [
            ControladorReportes::class,
            'cotizaciones',
        ])->name('reportes.cotizaciones');
        Route::get('/reportes/ventas', [
            ControladorReportes::class,
            'ventas',
        ])->name('reportes.ventas');
    });

    Route::middleware('rol:administrador,comercial')->group(function () {
        Route::get('/ventas/registrar/{cotizacion}', [
            ControladorVentas::class,
            'crear',
        ])->name('ventas.crear');
        Route::put('/cotizaciones/{cotizacion}', [
            ControladorCotizaciones::class,
            'actualizar',
        ])->name('cotizaciones.actualizar');
        Route::post('/cotizaciones/{cotizacion}/enviar', [
            ControladorCotizaciones::class,
            'enviar',
        ])->name('cotizaciones.enviar');
        Route::post('/cotizaciones/{cotizacion}/correo', [
            ControladorCotizaciones::class,
            'enviarCorreo',
        ])->name('cotizaciones.correo');
        Route::post('/cotizaciones/{cotizacion}/rechazar', [
            ControladorCotizaciones::class,
            'rechazar',
        ])->name('cotizaciones.rechazar');
        Route::post('/cotizaciones/{cotizacion}/cancelar', [
            ControladorCotizaciones::class,
            'cancelar',
        ])->name('cotizaciones.cancelar');
        Route::post('/cotizaciones/{cotizacion}/aceptar', [
            ControladorCotizaciones::class,
            'aceptar',
        ])->name('cotizaciones.aceptar');
        Route::post('/cotizaciones/{cotizacion}/venta', [
            ControladorVentas::class,
            'guardar',
        ])->name('ventas.guardar');
        Route::post('/cotizaciones', [
            ControladorCotizaciones::class,
            'guardar',
        ])->name('cotizaciones.guardar');
        Route::post('/api/cotizaciones', [
            ControladorCotizaciones::class,
            'guardarCompleta',
        ])->name('api.cotizaciones.guardar');
        Route::post('/cotizaciones/{cotizacion}/partidas', [
            ControladorPartidas::class,
            'guardar',
        ])->name('cotizaciones.partidas.guardar');
        Route::delete('/cotizaciones/{cotizacion}/partidas/{partida}', [
            ControladorPartidas::class,
            'eliminar',
        ])->name('cotizaciones.partidas.eliminar');
        Route::put('/cotizaciones/{cotizacion}/partidas/{partida}', [
            ControladorPartidas::class,
            'actualizar',
        ])->name('cotizaciones.partidas.actualizar');
        Route::get('/inventario', [ControladorProductos::class, 'listar'])->name('inventario.listado');
        Route::get('/inventario/productos', [ControladorProductos::class, 'listar'])->name('inventario.productos.listado');
        Route::post('/inventario/productos', [ControladorProductos::class, 'guardar'])->name('inventario.productos.guardar');
        Route::get('/inventario/productos/{producto}', [ControladorProductos::class, 'mostrar'])->name('inventario.productos.detalle');
        Route::put('/inventario/productos/{producto}', [ControladorProductos::class, 'actualizar'])->name('inventario.productos.actualizar');
        Route::patch('/inventario/productos/{producto}/estado', [ControladorProductos::class, 'actualizarEstado'])->name('inventario.productos.estado');
        Route::get('/inventario/servicios', [ControladorServicios::class, 'listar'])->name('inventario.servicios.listado');
        Route::post('/inventario/servicios', [ControladorServicios::class, 'guardar'])->name('inventario.servicios.guardar');
        Route::get('/inventario/servicios/{servicio}', [ControladorServicios::class, 'mostrar'])->name('inventario.servicios.detalle');
        Route::put('/inventario/servicios/{servicio}', [ControladorServicios::class, 'actualizar'])->name('inventario.servicios.actualizar');
        Route::patch('/inventario/servicios/{servicio}/estado', [ControladorServicios::class, 'actualizarEstado'])->name('inventario.servicios.estado');
        Route::post('/inventario/categorias', [
            ControladorCategorias::class,
            'guardar',
        ])->name('inventario.categorias.guardar');
        Route::get('/inventario/categorias', [
            ControladorCategorias::class,
            'listar',
        ])->name('inventario.categorias.listado');
        Route::get('/inventario/categorias/{categoria}', [
            ControladorCategorias::class,
            'mostrar',
        ])->name('inventario.categorias.detalle');
        Route::put('/inventario/categorias/{categoria}', [
            ControladorCategorias::class,
            'actualizar',
        ])->name('inventario.categorias.actualizar');
        Route::patch('/inventario/categorias/{categoria}/estado', [
            ControladorCategorias::class,
            'actualizarEstado',
        ])->name('inventario.categorias.estado');
        Route::get('/catalogo', fn () => redirect()->route('inventario.listado'))
            ->name('catalogo.listado');
        Route::get('/clientes', [
            ControladorClientes::class,
            'listar',
        ])->name('clientes.listado');
        Route::post('/clientes', [
            ControladorClientes::class,
            'guardar',
        ])->name('clientes.guardar');
        Route::put('/clientes/{cliente}', [
            ControladorClientes::class,
            'actualizar',
        ])->name('clientes.actualizar');
    });

    Route::middleware('rol:administrador')->group(function () {
        Route::get('/administracion/usuarios', [
            ControladorUsuarios::class,
            'listar',
        ])->name('administracion.usuarios.listado');
        Route::post('/administracion/usuarios', [
            ControladorUsuarios::class,
            'guardar',
        ])->name('administracion.usuarios.guardar');
        Route::put('/administracion/usuarios/{usuario}', [
            ControladorUsuarios::class,
            'actualizar',
        ])->name('administracion.usuarios.actualizar');
        Route::patch('/administracion/usuarios/{usuario}/estado', [
            ControladorUsuarios::class,
            'actualizarEstado',
        ])->name('administracion.usuarios.estado');
    });
});
