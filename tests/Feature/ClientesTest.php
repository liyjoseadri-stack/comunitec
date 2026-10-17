<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientesTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_listado_separa_el_alta_y_la_edicion_en_pantallas_enfocadas(): void
    {
        $usuario = Usuario::factory()->create(['rol' => Usuario::ROL_COMERCIAL]);
        $cliente = $this->crearCliente(['nombre' => 'Cliente para editar']);

        $this->actingAs($usuario)
            ->get('/clientes')
            ->assertOk()
            ->assertSee(route('clientes.crear'))
            ->assertSee(route('clientes.editar', $cliente))
            ->assertDontSee('id="nombre-cliente"', false);

        $this->get('/clientes/crear')
            ->assertOk()
            ->assertSee('Registrar cliente')
            ->assertSee('id="nombre-cliente"', false);

        $this->get("/clientes/{$cliente->id}/editar")
            ->assertOk()
            ->assertSee('Editar cliente')
            ->assertSee('Cliente para editar');
    }

    public function test_el_listado_busca_en_los_datos_de_contacto_y_conserva_el_filtro_al_paginar(): void
    {
        $usuario = Usuario::factory()->create(['rol' => Usuario::ROL_ADMINISTRADOR]);
        $coincidente = $this->crearCliente([
            'nombre' => 'Empresa encontrada',
            'rfc' => 'EMP010101AB1',
            'correo' => 'ventas@especial.test',
            'telefono' => '9611234567',
            'direccion' => 'Avenida Tecnológica',
        ]);

        foreach (range(1, 16) as $indice) {
            $this->crearCliente([
                'nombre' => sprintf('Cliente %02d', $indice),
                'rfc' => sprintf('RFC%010d', $indice),
                'correo' => "cliente{$indice}@example.test",
            ]);
        }

        $respuesta = $this->actingAs($usuario)->get('/clientes?buscar=especial');

        $respuesta->assertOk()
            ->assertSee($coincidente->nombre)
            ->assertDontSee('Cliente 01');

        $paginada = $this->get('/clientes?buscar=Cliente');
        $paginada->assertOk()
            ->assertSee('Página 1 de 2')
            ->assertSee('buscar=Cliente', false);
    }

    public function test_consulta_no_puede_abrir_las_pantallas_de_gestion_de_clientes(): void
    {
        $usuario = Usuario::factory()->create(['rol' => Usuario::ROL_CONSULTA]);
        $cliente = $this->crearCliente();

        $this->actingAs($usuario)->get('/clientes/crear')->assertForbidden();
        $this->get("/clientes/{$cliente->id}/editar")->assertForbidden();
    }

    public function test_el_comercial_puede_crear_una_persona_moral(): void
    {
        $usuario = Usuario::factory()->create([
            'rol' => Usuario::ROL_COMERCIAL,
        ]);

        $this->actingAs($usuario)->post('/clientes', [

            'tipo' => 'moral',
            'nombre' => 'Comunitec SA de CV',
            'rfc' => 'COM010101AB1',

            'correo' => 'contacto@example.com',
            'telefono' => '9610000000',

            'direccion' => 'Av. Central 1',
            'codigo_postal' => '29000',

        ])->assertRedirect('/clientes');

        $this->assertDatabaseHas('clientes', [
            'rfc' => 'COM010101AB1',
            'tipo' => 'moral',
        ]);
    }

    public function test_el_comercial_puede_actualizar_un_cliente(): void
    {
        $usuario = Usuario::factory()->create([
            'rol' => Usuario::ROL_COMERCIAL,
        ]);
        $cliente = Cliente::create([
            'tipo' => 'fisica',
            'nombre' => 'Ana',
            'rfc' => 'AAAA010101AA1',
            'correo' => 'ana@example.com',
            'telefono' => '1',
            'direccion' => 'Uno',
            'codigo_postal' => '29000',
        ]);
        $this->actingAs($usuario)->put("/clientes/{$cliente->id}", [
            'tipo' => 'fisica',
            'nombre' => 'Ana García',
            'rfc' => 'AAAA010101AA1',
            'correo' => 'ana@example.com',
            'telefono' => '2',
            'direccion' => 'Dos',
            'codigo_postal' => '29001',
        ])->assertRedirect('/clientes');
        $this->assertDatabaseHas('clientes', [
            'id' => $cliente->id,
            'nombre' => 'Ana García',
            'telefono' => '2',
        ]);
    }

    private function crearCliente(array $atributos = []): Cliente
    {
        static $secuencia = 0;
        $secuencia++;

        return Cliente::create(array_merge([
            'tipo' => 'moral',
            'nombre' => "Cliente {$secuencia}",
            'rfc' => sprintf('CLI%010d', $secuencia),
            'correo' => "contacto{$secuencia}@example.test",
            'telefono' => '9610000000',
            'direccion' => 'Av. Central 1',
            'codigo_postal' => '29000',
        ], $atributos));
    }
}
