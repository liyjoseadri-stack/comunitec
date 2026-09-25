<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Cliente;
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
