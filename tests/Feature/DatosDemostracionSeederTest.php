<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\Producto;
use App\Models\Servicio;
use Database\Seeders\DatosDemostracionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatosDemostracionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_carga_un_catalogo_inicial_de_computo_sin_duplicarlo(): void
    {
        $this->seed(DatosDemostracionSeeder::class);
        $this->seed(DatosDemostracionSeeder::class);

        $this->assertSame(6, Categoria::count());
        $this->assertSame(13, Producto::count());
        $this->assertSame(6, Servicio::count());
        $this->assertSame(4, Cliente::count());
        $this->assertSame(13, Cotizacion::count());
        $this->assertSame(2, Cotizacion::where('estado', Cotizacion::ESTADO_BORRADOR)->count());
        $this->assertSame(4, Cotizacion::where('estado', Cotizacion::ESTADO_PENDIENTE)->count());
        $this->assertSame(3, Cotizacion::where('estado', Cotizacion::ESTADO_ACEPTADA)->count());
        $this->assertSame(1, Cotizacion::where('estado', Cotizacion::ESTADO_RECHAZADA)->count());
        $this->assertSame(2, Cotizacion::where('estado', Cotizacion::ESTADO_CANCELADA)->count());
        $this->assertSame(1, Cotizacion::where('estado', Cotizacion::ESTADO_VENCIDA)->count());
        $this->assertDatabaseHas('cotizaciones', [
            'folio' => 'DEMO-PENDIENTE-MANANA',
            'estado' => Cotizacion::ESTADO_PENDIENTE,
        ]);
        $this->assertDatabaseHas('cotizaciones', [
            'folio' => 'DEMO-ACEPTADA-PROXIMA',
            'estado' => Cotizacion::ESTADO_ACEPTADA,
        ]);
        $this->assertDatabaseHas('productos', [
            'codigo' => 'RED-AP-U6PLUS',
            'requiere_numero_serie' => true,
            'existencias' => 10,
        ]);
        $this->assertDatabaseHas('servicios', [
            'codigo' => 'SER-NODO-CAT6',
            'unidad' => 'nodo',
        ]);
    }
}
