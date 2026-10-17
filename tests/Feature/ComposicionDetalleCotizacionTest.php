<?php

namespace Tests\Feature;

use Tests\TestCase;

class ComposicionDetalleCotizacionTest extends TestCase
{
    public function test_el_detalle_de_cotizacion_se_compone_con_parciales_coherentes(): void
    {
        $vista = file_get_contents(resource_path('views/cotizaciones/detalle.blade.php'));

        $parciales = [
            'cotizaciones.parciales.detalle.resumen',
            'cotizaciones.parciales.detalle.notas',
            'cotizaciones.parciales.detalle.historial-correo',
            'cotizaciones.parciales.detalle.seguimiento-acciones',
            'cotizaciones.parciales.detalle.formulario-concepto',
            'cotizaciones.parciales.detalle.conceptos',
        ];

        foreach ($parciales as $parcial) {
            $this->assertStringContainsString("@include('{$parcial}')", $vista);
            $this->assertFileExists(resource_path('views/'.str_replace('.', '/', $parcial).'.blade.php'));
        }

        $this->assertLessThanOrEqual(80, count(file(resource_path('views/cotizaciones/detalle.blade.php'))));
    }
}
