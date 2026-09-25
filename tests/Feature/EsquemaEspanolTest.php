<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EsquemaEspanolTest extends TestCase
{
    use RefreshDatabase;

    public function test_las_tablas_propias_del_sistema_tienen_nombres_en_espanol(): void
    {
        foreach ([
            'usuarios',
            'clientes',
            'categorias',
            'articulos_catalogo',
            'piezas_inventario',
            'cotizaciones',
            'partidas_cotizacion',
            'envios_correo_cotizacion',
            'ventas',
            'partidas_venta',
        ] as $tabla) {
            $this->assertTrue(Schema::hasTable($tabla), "No existe la tabla {$tabla}.");
        }
    }

    public function test_no_quedan_tablas_propias_con_nombres_anteriores(): void
    {
        foreach ([
            'users',
            'customers',
            'categories',
            'catalog_items',
            'inventory_units',
            'quotes',
            'quote_lines',
            'quote_email_deliveries',
            'sales',
            'sale_lines',
        ] as $tabla) {
            $this->assertFalse(Schema::hasTable($tabla), "Todavía existe la tabla anterior {$tabla}.");
        }
    }
}
