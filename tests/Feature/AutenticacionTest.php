<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AutenticacionTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_invitado_es_redirigido_al_inicio_de_sesion(): void
    {
        $this->get('/panel')->assertRedirect('/iniciar-sesion');
    }

    public function test_el_usuario_activo_puede_ingresar_y_ver_el_panel(): void
    {
        $user = Usuario::factory()->create([

            'password' => Hash::make('secret-password'),

        ]);

        $this->post('/iniciar-sesion', [

            'email' => $user->email,

            'password' => 'secret-password',

        ])->assertRedirect('/panel');

        $this->assertAuthenticatedAs($user);
        $this->get('/panel')->assertOk()->assertSee('Panel principal');
    }

    public function test_el_usuario_inactivo_no_puede_ingresar(): void
    {
        $user = Usuario::factory()->create([

            'active' => false,

            'password' => Hash::make('secret-password'),

        ]);

        $this->from('/iniciar-sesion')->post('/iniciar-sesion', [

            'email' => $user->email,

            'password' => 'secret-password',

        ])->assertRedirect('/iniciar-sesion')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
