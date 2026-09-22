<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use RefreshDatabase;

    public function test_commercial_user_can_create_a_moral_customer(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMMERCIAL]);

        $this->actingAs($user)->post('/clientes', [
            'type' => 'moral', 'name' => 'Comunitec SA de CV', 'rfc' => 'COM010101AB1',
            'email' => 'contacto@example.com', 'phone' => '9610000000',
            'address' => 'Av. Central 1', 'postal_code' => '29000',
        ])->assertRedirect('/clientes');

        $this->assertDatabaseHas('customers', ['rfc' => 'COM010101AB1', 'type' => 'moral']);
    }

    public function test_commercial_user_can_update_a_customer(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMMERCIAL]);
        $customer = \App\Models\Customer::create(['type'=>'fisica','name'=>'Ana','rfc'=>'AAAA010101AA1','email'=>'ana@example.com','phone'=>'1','address'=>'Uno','postal_code'=>'29000']);
        $this->actingAs($user)->put("/clientes/{$customer->id}", ['type'=>'fisica','name'=>'Ana García','rfc'=>'AAAA010101AA1','email'=>'ana@example.com','phone'=>'2','address'=>'Dos','postal_code'=>'29001'])->assertRedirect('/clientes');
        $this->assertDatabaseHas('customers', ['id'=>$customer->id,'name'=>'Ana García','phone'=>'2']);
    }
}
