<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientesTest extends TestCase
{
    use RefreshDatabase;

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
}
