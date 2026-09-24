<?php

use App\Http\Controllers\Admin\ControladorUsuarios;
use App\Http\Controllers\Auth\ControladorSesion;
use App\Http\Controllers\ControladorCatalogo;
use App\Http\Controllers\ControladorClientes;
use App\Http\Controllers\ControladorCotizaciones;
use App\Http\Controllers\ControladorInventario;
use App\Http\Controllers\ControladorPanel;
use App\Http\Controllers\ControladorPartidas;
use App\Http\Controllers\ControladorReportes;
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
    Route::middleware('role:admin,comercial,consulta')->group(function () {
        Route::get('/cotizaciones', [
            ControladorCotizaciones::class,
            'listar',
        ])->name('cotizaciones.listado');
        Route::get('/cotizaciones/{quote}', [
            ControladorCotizaciones::class,
            'mostrar',
        ])->name('cotizaciones.detalle');
        Route::get('/cotizaciones/{quote}/pdf', [
            ControladorCotizaciones::class,
            'pdf',
        ])->name('cotizaciones.pdf');
        Route::get('/ventas', [
            ControladorVentas::class,
            'listar',
        ])->name('ventas.listado');
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

    Route::middleware('role:admin,comercial')->group(function () {
        Route::put('/cotizaciones/{quote}', [
            ControladorCotizaciones::class,
            'actualizar',
        ])->name('cotizaciones.actualizar');
        Route::post('/cotizaciones/{quote}/enviar', [
            ControladorCotizaciones::class,
            'enviar',
        ])->name('cotizaciones.enviar');
        Route::post('/cotizaciones/{quote}/correo', [
            ControladorCotizaciones::class,
            'enviarCorreo',
        ])->name('cotizaciones.correo');
        Route::post('/cotizaciones/{quote}/rechazar', [
            ControladorCotizaciones::class,
            'rechazar',
        ])->name('cotizaciones.rechazar');
        Route::post('/cotizaciones/{quote}/cancelar', [
            ControladorCotizaciones::class,
            'cancelar',
        ])->name('cotizaciones.cancelar');
        Route::post('/cotizaciones/{quote}/aceptar', [
            ControladorCotizaciones::class,
            'aceptar',
        ])->name('cotizaciones.aceptar');
        Route::post('/cotizaciones/{quote}/venta', [
            ControladorVentas::class,
            'guardar',
        ])->name('ventas.guardar');
        Route::post('/cotizaciones', [
            ControladorCotizaciones::class,
            'guardar',
        ])->name('cotizaciones.guardar');
        Route::post('/cotizaciones/{quote}/partidas', [
            ControladorPartidas::class,
            'guardar',
        ])->name('cotizaciones.partidas.guardar');
        Route::delete('/cotizaciones/{quote}/partidas/{line}', [
            ControladorPartidas::class,
            'eliminar',
        ])->name('cotizaciones.partidas.eliminar');
        Route::put('/cotizaciones/{quote}/partidas/{line}', [
            ControladorPartidas::class,
            'actualizar',
        ])->name('cotizaciones.partidas.actualizar');
        Route::get('/inventario', [
            ControladorInventario::class,
            'listar',
        ])->name('inventario.listado');
        Route::post('/inventario/categorias', [
            ControladorInventario::class,
            'guardarCategoria',
        ])->name('inventario.categorias');
        Route::post('/inventario/series', [
            ControladorInventario::class,
            'registrarPieza',
        ])->name('inventario.piezas');
        Route::get('/catalogo', [
            ControladorCatalogo::class,
            'listar',
        ])->name('catalogo.listado');
        Route::post('/catalogo', [
            ControladorCatalogo::class,
            'guardar',
        ])->name('catalogo.guardar');
        Route::get('/clientes', [
            ControladorClientes::class,
            'listar',
        ])->name('clientes.listado');
        Route::post('/clientes', [
            ControladorClientes::class,
            'guardar',
        ])->name('clientes.guardar');
        Route::put('/clientes/{customer}', [
            ControladorClientes::class,
            'actualizar',
        ])->name('clientes.actualizar');
    });

    Route::middleware('role:admin')->group(function () {
        Route::get('/administracion/usuarios', [
            ControladorUsuarios::class,
            'listar',
        ])->name('administracion.usuarios.listado');
        Route::post('/administracion/usuarios', [
            ControladorUsuarios::class,
            'guardar',
        ])->name('administracion.usuarios.guardar');
        Route::put('/administracion/usuarios/{user}', [
            ControladorUsuarios::class,
            'actualizar',
        ])->name('administracion.usuarios.actualizar');
        Route::patch('/administracion/usuarios/{user}/estado', [
            ControladorUsuarios::class,
            'actualizarEstado',
        ])->name('administracion.usuarios.estado');
    });
});
