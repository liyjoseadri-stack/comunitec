<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\Usuario;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RendimientoConsultasTest extends TestCase
{
    use RefreshDatabase;

    public function test_los_listados_cronologicos_tienen_indices_para_filtrar_y_ordenar(): void
    {
        $indicesCotizaciones = collect(Schema::getIndexes('cotizaciones'))->pluck('name');
        $indicesVentas = collect(Schema::getIndexes('ventas'))->pluck('name');

        $this->assertContains('cotizaciones_creadas_indice', $indicesCotizaciones);
        $this->assertContains('cotizaciones_estado_creadas_indice', $indicesCotizaciones);
        $this->assertContains('ventas_vendidas_indice', $indicesVentas);
    }

    public function test_el_historial_de_cotizaciones_limita_cada_pagina_y_conserva_el_filtro(): void
    {
        $usuario = Usuario::factory()->create(['rol' => Usuario::ROL_COMERCIAL]);
        $cliente = $this->cliente();

        foreach (range(1, 16) as $numero) {
            Cotizacion::create([
                'folio' => sprintf('COT-REND-%02d', $numero),
                'cliente_id' => $cliente->id,
                'usuario_id' => $usuario->id,
                'estado' => Cotizacion::ESTADO_PENDIENTE,
                'total' => 100,
            ]);
        }

        $respuesta = $this->actingAs($usuario)
            ->get(route('cotizaciones.listado', ['estado' => Cotizacion::ESTADO_PENDIENTE]));

        $respuesta->assertOk()
            ->assertViewHas('cotizaciones', function ($cotizaciones): bool {
                return $cotizaciones instanceof LengthAwarePaginator
                    && $cotizaciones->count() === 15
                    && $cotizaciones->total() === 16;
            })
            ->assertSee('estado=pendiente', false);
    }

    public function test_el_vencimiento_procesa_las_aceptadas_en_bloques_acotados(): void
    {
        $usuario = Usuario::factory()->create(['rol' => Usuario::ROL_COMERCIAL]);
        $cliente = $this->cliente();

        foreach (range(1, 101) as $numero) {
            Cotizacion::create([
                'folio' => sprintf('COT-VENCE-%03d', $numero),
                'cliente_id' => $cliente->id,
                'usuario_id' => $usuario->id,
                'estado' => Cotizacion::ESTADO_ACEPTADA,
                'aceptada_en' => now()->subDays(6),
                'entrega_limite_en' => now()->subMinute(),
                'total' => 100,
            ]);
        }

        $consultas = [];
        DB::listen(function (QueryExecuted $consulta) use (&$consultas): void {
            if (str_contains($consulta->sql, 'from "cotizaciones"')
                && in_array(Cotizacion::ESTADO_ACEPTADA, $consulta->bindings, true)) {
                $consultas[] = strtolower($consulta->sql);
            }
        });

        $this->artisan('cotizaciones:vencer')->assertSuccessful();

        $this->assertSame(101, Cotizacion::where('estado', Cotizacion::ESTADO_CANCELADA)->count());
        $this->assertNotEmpty($consultas);
        $this->assertTrue(
            collect($consultas)->contains(fn (string $sql): bool => str_contains($sql, 'limit 100')),
            'La consulta principal de cotizaciones aceptadas debe acotarse a bloques de 100.'
        );
    }

    private function cliente(): Cliente
    {
        return Cliente::create([
            'tipo' => 'moral',
            'nombre' => 'Cliente de rendimiento',
            'rfc' => 'REN010101AA1',
            'correo' => 'rendimiento@example.test',
            'telefono' => '9610000000',
            'direccion' => 'Domicilio de prueba',
            'codigo_postal' => '29000',
        ]);
    }
}
