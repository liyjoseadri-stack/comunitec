<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAdministrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_create_and_deactivate_a_user(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->post('/administracion/usuarios', [
            'name' => 'Ana Comercial',
            'email' => 'ana@example.com',
            'role' => User::ROLE_COMMERCIAL,
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ])->assertRedirect('/administracion/usuarios');

        $user = User::where('email', 'ana@example.com')->firstOrFail();
        $this->assertSame(User::ROLE_COMMERCIAL, $user->role);
        $this->assertTrue($user->active);

        $this->patch("/administracion/usuarios/{$user->id}/estado", ['active' => false])
            ->assertRedirect('/administracion/usuarios');

        $this->assertFalse($user->fresh()->active);
    }

    public function test_administrator_can_update_a_user(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create(['role' => User::ROLE_COMMERCIAL]);

        $this->actingAs($admin)->get('/administracion/usuarios')
            ->assertOk()
            ->assertSee('Editar');

        $this->actingAs($admin)->put("/administracion/usuarios/{$user->id}", [
            'name' => 'Usuario actualizado',
            'email' => 'actualizado@example.com',
            'role' => User::ROLE_CONSULTATION,
            'password' => '',
            'password_confirmation' => '',
        ])->assertRedirect('/administracion/usuarios');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Usuario actualizado',
            'email' => 'actualizado@example.com',
            'role' => User::ROLE_CONSULTATION,
        ]);
    }
}
