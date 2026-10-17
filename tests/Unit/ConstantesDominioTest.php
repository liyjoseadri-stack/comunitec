<?php

namespace Tests\Unit;

use App\Http\Requests\SolicitudGuardarCotizacionCompleta;
use App\Models\Cotizacion;
use App\Models\PartidaCotizacion;
use App\Models\PartidaVenta;
use App\Models\PiezaInventario;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ConstantesDominioTest extends TestCase
{
    public function test_estados_de_cotizacion_conservan_valores_y_etiquetas(): void
    {
        $etiquetas = [
            'borrador' => 'Borrador',
            'pendiente' => 'Pendiente',
            'aceptada' => 'Aceptada',
            'rechazada' => 'Rechazada',
            'cancelada' => 'Cancelada',
            'vencida' => 'Vencida',
            'venta' => 'Venta',
        ];

        $this->assertSame(array_keys($etiquetas), Cotizacion::estados());
        foreach ($etiquetas as $estado => $etiqueta) {
            $this->assertSame($estado, constant(Cotizacion::class.'::ESTADO_'.strtoupper($estado)));
            $this->assertSame($etiqueta, (new Cotizacion(['estado' => $estado]))->etiquetaEstado());
        }
        $this->assertSame('Sin definir', (new Cotizacion(['estado' => 'desconocido']))->etiquetaEstado());
    }

    public function test_partidas_comparten_tipos_canonicos_y_valores_persistidos(): void
    {
        foreach ([PartidaCotizacion::class, PartidaVenta::class] as $modelo) {
            $this->assertSame(['producto', 'servicio', 'otro'], $modelo::tipos());
            $this->assertSame('producto', $modelo::TIPO_PRODUCTO);
            $this->assertSame('servicio', $modelo::TIPO_SERVICIO);
            $this->assertSame('otro', $modelo::TIPO_OTRO);
        }
    }

    public function test_piezas_conservan_sus_tres_estados_persistidos(): void
    {
        $this->assertSame(['disponible', 'reservada', 'entregada'], PiezaInventario::estados());
        $this->assertSame('disponible', PiezaInventario::ESTADO_DISPONIBLE);
        $this->assertSame('reservada', PiezaInventario::ESTADO_RESERVADA);
        $this->assertSame('entregada', PiezaInventario::ESTADO_ENTREGADA);
    }

    public function test_creacion_completa_acepta_solo_tipos_de_catalogo_canonicos(): void
    {
        $reglas = (new SolicitudGuardarCotizacionCompleta)->rules()['items.*.tipo'];
        foreach (PartidaCotizacion::tipos() as $tipo) {
            $validador = Validator::make(['tipo' => $tipo], ['tipo' => $reglas]);
            $this->assertSame($tipo !== PartidaCotizacion::TIPO_OTRO, $validador->passes());
        }
        foreach (['desconocido', '', null] as $tipo) {
            $this->assertTrue(Validator::make(['tipo' => $tipo], ['tipo' => $reglas])->fails());
        }
    }
}
