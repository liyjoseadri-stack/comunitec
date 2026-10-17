<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AutenticacionTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_inicio_de_sesion_limita_intentos_fallidos(): void
    {
        $usuario = Usuario::factory()->create([
            'contrasena' => Hash::make('secret-password'),
        ]);

        for ($intento = 0; $intento < 5; $intento++) {
            $this->from('/iniciar-sesion')->post('/iniciar-sesion', [
                'correo' => $usuario->correo,
                'contrasena' => 'incorrecta',
            ])->assertRedirect('/iniciar-sesion')->assertSessionHasErrors([
                'correo' => 'Las credenciales no son correctas.',
            ]);
        }

        $this->post('/iniciar-sesion', [
            'correo' => $usuario->correo,
            'contrasena' => 'secret-password',
        ])->assertRedirect('/iniciar-sesion')->assertSessionHasErrors('correo');

        $this->assertStringContainsString('segundos', session('errors')->first('correo'));
        $this->assertGuest();
    }

    public function test_el_limite_agrupa_el_mismo_correo_con_mayusculas_y_espacios(): void
    {
        $usuario = Usuario::factory()->create([
            'correo' => 'acceso@example.com',
            'contrasena' => Hash::make('secret-password'),
        ]);

        foreach (['acceso@example.com', 'ACCESO@example.com', ' acceso@example.com ', 'Acceso@Example.Com', ' ACCESO@EXAMPLE.COM '] as $correo) {
            $this->from('/iniciar-sesion')->post('/iniciar-sesion', [
                'correo' => $correo,
                'contrasena' => 'incorrecta',
            ])->assertSessionHasErrors([
                'correo' => 'Las credenciales no son correctas.',
            ]);
        }

        $this->post('/iniciar-sesion', [
            'correo' => $usuario->correo,
            'contrasena' => 'secret-password',
        ])->assertRedirect('/iniciar-sesion')->assertSessionHasErrors('correo');

        $this->assertStringContainsString('segundos', session('errors')->first('correo'));
        $this->assertGuest();
    }

    public function test_el_limite_permite_ingresar_despues_de_sesenta_segundos(): void
    {
        $usuario = Usuario::factory()->create([
            'contrasena' => Hash::make('secret-password'),
        ]);

        for ($intento = 0; $intento < 5; $intento++) {
            $this->post('/iniciar-sesion', [
                'correo' => $usuario->correo,
                'contrasena' => 'incorrecta',
            ]);
        }

        $this->travel(61)->seconds();

        $this->post('/iniciar-sesion', [
            'correo' => $usuario->correo,
            'contrasena' => 'secret-password',
        ])->assertRedirect('/panel');

        $this->assertAuthenticatedAs($usuario);
    }

    public function test_un_inicio_de_sesion_correcto_limpia_los_intentos_fallidos(): void
    {
        $usuario = Usuario::factory()->create([
            'contrasena' => Hash::make('secret-password'),
        ]);

        for ($intento = 0; $intento < 4; $intento++) {
            $this->post('/iniciar-sesion', [
                'correo' => $usuario->correo,
                'contrasena' => 'incorrecta',
            ]);
        }

        $this->post('/iniciar-sesion', [
            'correo' => $usuario->correo,
            'contrasena' => 'secret-password',
        ])->assertRedirect('/panel');

        $this->post('/cerrar-sesion');
        $this->assertGuest();

        for ($intento = 0; $intento < 5; $intento++) {
            $this->post('/iniciar-sesion', [
                'correo' => $usuario->correo,
                'contrasena' => 'incorrecta',
            ])->assertSessionHasErrors([
                'correo' => 'Las credenciales no son correctas.',
            ]);
        }
    }

    public function test_el_inicio_de_sesion_usa_la_identidad_visual_corporativa(): void
    {
        $this->get('/iniciar-sesion')
            ->assertOk()
            ->assertSee('class="acceso"', false)
            ->assertSee('logo-comunitec-transparente.png', false)
            ->assertSee('for="correo"', false)
            ->assertSee('for="contrasena"', false);
    }

    public function test_el_invitado_es_redirigido_al_inicio_de_sesion(): void
    {
        $this->get('/panel')->assertRedirect('/iniciar-sesion');
    }

    public function test_el_usuario_activo_puede_ingresar_y_ver_el_panel(): void
    {
        $usuario = Usuario::factory()->create([

            'contrasena' => Hash::make('secret-password'),

        ]);

        $this->post('/iniciar-sesion', [

            'correo' => $usuario->correo,

            'contrasena' => 'secret-password',

        ])->assertRedirect('/panel');

        $this->assertAuthenticatedAs($usuario);
        $this->get('/panel')->assertOk()->assertSee('Panel principal');
    }

    public function test_el_usuario_inactivo_no_puede_ingresar(): void
    {
        $usuario = Usuario::factory()->create([

            'activo' => false,

            'contrasena' => Hash::make('secret-password'),

        ]);

        $this->from('/iniciar-sesion')->post('/iniciar-sesion', [

            'correo' => $usuario->correo,

            'contrasena' => 'secret-password',

        ])->assertRedirect('/iniciar-sesion')
            ->assertSessionHasErrors('correo');

        $this->assertGuest();
    }

    public function test_un_usuario_desactivado_pierde_una_sesion_ya_iniciada(): void
    {
        $usuario = Usuario::factory()->create(['activo' => true]);

        $this->actingAs($usuario)->withSession([
            'dato_privado' => 'sesion anterior',
            '_token' => 'token-csrf-anterior',
        ]);
        $identificadorAnterior = session()->getId();

        Usuario::whereKey($usuario->id)->update(['activo' => false]);

        $this->get('/panel')
            ->assertRedirect('/iniciar-sesion')
            ->assertSessionMissing('dato_privado')
            ->assertSessionHasErrors([
                'correo' => 'Tu cuenta está inactiva. Contacta al administrador.',
            ]);

        $this->assertGuest();
        $this->assertNotSame($identificadorAnterior, session()->getId());
        $this->assertNotSame('token-csrf-anterior', session()->token());
        $this->assertNotEmpty(session()->token());
    }
}
