<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PerfilTest extends TestCase
{
    use RefreshDatabase;

    public function test_cambiar_contrasena_rota_el_token_de_recuerdo_y_conserva_la_sesion_actual(): void
    {
        $tokenAnterior = str_repeat('a', 60);
        $usuario = Usuario::factory()->create([
            'contrasena' => 'contrasena-anterior',
            'token_recuerdo' => $tokenAnterior,
        ]);

        $this->actingAs($usuario)->withSession(['dato_privado' => 'sesion actual'])
            ->put(route('perfil.contrasena'), [
                'contrasena_actual' => 'contrasena-anterior',
                'contrasena' => 'contrasena-nueva',
                'contrasena_confirmation' => 'contrasena-nueva',
            ])
            ->assertRedirect(route('perfil.mostrar'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('estado', 'Contraseña actualizada.')
            ->assertSessionHas('dato_privado', 'sesion actual');

        $usuario->refresh();

        $this->assertTrue(Hash::check('contrasena-nueva', $usuario->contrasena));
        $this->assertFalse(Hash::check('contrasena-anterior', $usuario->contrasena));
        $this->assertNotSame($tokenAnterior, $usuario->getRememberToken());
        $this->assertSame(60, strlen($usuario->getRememberToken()));
        $this->assertAuthenticatedAs($usuario);
        $this->get(route('perfil.mostrar'))->assertOk();
    }
}
