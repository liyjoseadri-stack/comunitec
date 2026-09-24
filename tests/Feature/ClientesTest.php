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
        $user = Usuario::factory()->create([
            'role' => Usuario::ROL_COMERCIAL,
        ]);

        $this->actingAs($user)->post('/clientes', [

            'type' => 'moral',
            'name' => 'Comunitec SA de CV',
            'rfc' => 'COM010101AB1',

            'email' => 'contacto@example.com',
            'phone' => '9610000000',

            'address' => 'Av. Central 1',
            'postal_code' => '29000',

        ])->assertRedirect('/clientes');

        $this->assertDatabaseHas('customers', [
            'rfc' => 'COM010101AB1',
            'type' => 'moral',
        ]);
    }

    public function test_el_comercial_puede_actualizar_un_cliente(): void
    {
        $user = Usuario::factory()->create([
            'role' => Usuario::ROL_COMERCIAL,
        ]);
        $customer = Cliente::create([
            'type' => 'fisica',
            'name' => 'Ana',
            'rfc' => 'AAAA010101AA1',
            'email' => 'ana@example.com',
            'phone' => '1',
            'address' => 'Uno',
            'postal_code' => '29000',
        ]);
        $this->actingAs($user)->put("/clientes/{$customer->id}", [
            'type' => 'fisica',
            'name' => 'Ana García',
            'rfc' => 'AAAA010101AA1',
            'email' => 'ana@example.com',
            'phone' => '2',
            'address' => 'Dos',
            'postal_code' => '29001',
        ])->assertRedirect('/clientes');
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Ana García',
            'phone' => '2',
        ]);
    }
}
