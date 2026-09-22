<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_access_user_administration(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]))
            ->get('/administracion/usuarios')
            ->assertOk()
            ->assertSee('Administración de usuarios');
    }

    public function test_commercial_user_cannot_access_user_administration(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_COMMERCIAL]))
            ->get('/administracion/usuarios')
            ->assertForbidden();
    }

    public function test_consultation_user_cannot_access_user_administration(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_CONSULTATION]))
            ->get('/administracion/usuarios')
            ->assertForbidden();
    }
}
