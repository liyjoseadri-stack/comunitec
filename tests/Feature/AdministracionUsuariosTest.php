<?php

namespace Tests\Feature;

use App\Models\Usuario;
use App\Services\ServicioAdministracionUsuarios;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AdministracionUsuariosTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_administrador_no_puede_desactivarse_a_si_mismo(): void
    {
        $administrador = Usuario::factory()->create(['rol' => Usuario::ROL_ADMINISTRADOR]);
        Usuario::factory()->create(['rol' => Usuario::ROL_ADMINISTRADOR]);

        $this->actingAs($administrador)->from('/administracion/usuarios')
            ->patch("/administracion/usuarios/{$administrador->id}/estado", ['activo' => false])
            ->assertRedirect('/administracion/usuarios')
            ->assertSessionHasErrors('usuario');

        $this->assertTrue($administrador->fresh()->activo);
    }

    public function test_no_se_puede_desactivar_al_ultimo_administrador_activo(): void
    {
        $administrador = Usuario::factory()->create(['rol' => Usuario::ROL_ADMINISTRADOR]);

        $this->actingAs($administrador)->from('/administracion/usuarios')
            ->patch("/administracion/usuarios/{$administrador->id}/estado", ['activo' => false])
            ->assertRedirect('/administracion/usuarios')
            ->assertSessionHasErrors('usuario');

        $this->assertTrue($administrador->fresh()->activo);
        $this->assertSame(Usuario::ROL_ADMINISTRADOR, $administrador->fresh()->rol);
    }

    public function test_no_se_puede_quitar_el_rol_propio_desde_el_endpoint_administrativo(): void
    {
        $administrador = Usuario::factory()->create(['rol' => Usuario::ROL_ADMINISTRADOR]);

        $this->actingAs($administrador)
            ->put("/administracion/usuarios/{$administrador->id}", [
                'nombre' => 'Cambio rechazado',
                'correo' => 'rechazado@example.com',
                'rol' => Usuario::ROL_CONSULTA,
                'contrasena' => null,
            ])
            ->assertForbidden();

        $this->assertSame(Usuario::ROL_ADMINISTRADOR, $administrador->fresh()->rol);
        $this->assertSame($administrador->nombre, $administrador->fresh()->nombre);
        $this->assertSame($administrador->correo, $administrador->fresh()->correo);
    }

    public function test_un_administrador_puede_desactivar_a_otro_si_queda_un_administrador_activo(): void
    {
        $administrador = Usuario::factory()->create(['rol' => Usuario::ROL_ADMINISTRADOR]);
        $otro = Usuario::factory()->create(['rol' => Usuario::ROL_ADMINISTRADOR]);

        $this->actingAs($administrador)
            ->patch("/administracion/usuarios/{$otro->id}/estado", ['activo' => false])
            ->assertRedirect('/administracion/usuarios')
            ->assertSessionHasNoErrors();

        $this->assertFalse($otro->fresh()->activo);
        $this->assertTrue($administrador->fresh()->activo);
    }

    public function test_el_administrador_puede_crear_y_desactivar_usuarios(): void
    {
        $administrador = Usuario::factory()->create([
            'rol' => Usuario::ROL_ADMINISTRADOR,
        ]);

        $this->actingAs($administrador)->post('/administracion/usuarios', [

            'nombre' => 'Ana Comercial',

            'correo' => 'ana@example.com',

            'rol' => Usuario::ROL_COMERCIAL,

            'contrasena' => 'secret-password',

            'contrasena_confirmation' => 'secret-password',

        ])->assertRedirect('/administracion/usuarios');

        $usuario = Usuario::where('correo', 'ana@example.com')->firstOrFail();
        $this->assertSame(Usuario::ROL_COMERCIAL, $usuario->rol);
        $this->assertTrue($usuario->activo);
        $this->assertNotSame('secret-password', $usuario->contrasena);
        $this->assertTrue(Hash::check('secret-password', $usuario->contrasena));

        $this->patch("/administracion/usuarios/{$usuario->id}/estado", [
            'activo' => false,
        ])
            ->assertRedirect('/administracion/usuarios');

        $this->assertFalse($usuario->fresh()->activo);
    }

    public function test_el_administrador_puede_actualizar_usuarios(): void
    {
        $administrador = Usuario::factory()->create([
            'rol' => Usuario::ROL_ADMINISTRADOR,
        ]);
        $usuario = Usuario::factory()->create([
            'rol' => Usuario::ROL_COMERCIAL,
        ]);

        $this->actingAs($administrador)->get("/administracion/usuarios/{$usuario->id}/editar")
            ->assertOk()
            ->assertSee('Editar usuario')
            ->assertSee($usuario->nombre);

        $this->actingAs($administrador)->put("/administracion/usuarios/{$usuario->id}", [

            'nombre' => 'Usuario actualizado',

            'correo' => 'actualizado@example.com',

            'rol' => Usuario::ROL_CONSULTA,

            'contrasena' => '',

            'contrasena_confirmation' => '',

        ])->assertRedirect('/administracion/usuarios');

        $this->assertDatabaseHas('usuarios', [

            'id' => $usuario->id,

            'nombre' => 'Usuario actualizado',

            'correo' => 'actualizado@example.com',

            'rol' => Usuario::ROL_CONSULTA,

        ]);
    }

    public function test_desactivar_un_usuario_rota_su_token_de_recuerdo(): void
    {
        $administrador = Usuario::factory()->create([
            'rol' => Usuario::ROL_ADMINISTRADOR,
        ]);
        $usuario = Usuario::factory()->create(['activo' => true]);
        $tokenAnterior = str_repeat('a', 60);
        $usuario->setRememberToken($tokenAnterior);
        $usuario->save();

        $this->actingAs($administrador)
            ->patch("/administracion/usuarios/{$usuario->id}/estado", ['activo' => false])
            ->assertRedirect('/administracion/usuarios');

        $usuario->refresh();
        $this->assertFalse($usuario->activo);
        $this->assertNotSame($tokenAnterior, $usuario->getRememberToken());
        $this->assertSame(60, strlen($usuario->getRememberToken()));
    }

    public function test_reactivar_un_usuario_conserva_su_token_de_recuerdo(): void
    {
        $administrador = Usuario::factory()->create([
            'rol' => Usuario::ROL_ADMINISTRADOR,
        ]);
        $usuario = Usuario::factory()->create(['activo' => false]);
        $tokenAnterior = str_repeat('b', 60);
        $usuario->setRememberToken($tokenAnterior);
        $usuario->save();

        $this->actingAs($administrador)
            ->patch("/administracion/usuarios/{$usuario->id}/estado", ['activo' => true])
            ->assertRedirect('/administracion/usuarios');

        $usuario->refresh();
        $this->assertTrue($usuario->activo);
        $this->assertSame($tokenAnterior, $usuario->getRememberToken());
    }

    public function test_el_administrador_edita_su_cuenta_desde_mi_perfil(): void
    {
        $administrador = Usuario::factory()->create([
            'rol' => Usuario::ROL_ADMINISTRADOR,
        ]);
        $otroUsuario = Usuario::factory()->create([
            'rol' => Usuario::ROL_COMERCIAL,
        ]);

        $this->actingAs($administrador)
            ->get('/administracion/usuarios')
            ->assertOk()
            ->assertDontSee('href="'.route('administracion.usuarios.editar', $administrador).'"', false)
            ->assertSee('href="'.route('administracion.usuarios.editar', $otroUsuario).'"', false);

        $this->actingAs($administrador)
            ->get(route('administracion.usuarios.editar', $administrador))
            ->assertForbidden();

        $this->put(route('administracion.usuarios.actualizar', $administrador), [
            'nombre' => 'Cambio indebido',
            'correo' => 'cambio-indebido@example.test',
            'rol' => Usuario::ROL_COMERCIAL,
            'contrasena' => 'OtraContrasena123!',
            'contrasena_confirmation' => 'OtraContrasena123!',
        ])->assertForbidden();

        $administrador->refresh();
        $this->assertNotSame('Cambio indebido', $administrador->nombre);
        $this->assertSame(Usuario::ROL_ADMINISTRADOR, $administrador->rol);
    }

    public function test_el_listado_filtra_busca_y_conserva_los_filtros_al_paginar(): void
    {
        $administrador = Usuario::factory()->create([
            'nombre' => 'Administrador principal',
            'rol' => Usuario::ROL_ADMINISTRADOR,
        ]);
        Usuario::factory()->create([
            'nombre' => 'Ana Comercial',
            'correo' => 'ana.comercial@example.com',
            'rol' => Usuario::ROL_COMERCIAL,
            'activo' => true,
        ]);
        Usuario::factory()->create([
            'nombre' => 'Ana Consulta',
            'correo' => 'ana.consulta@example.com',
            'rol' => Usuario::ROL_CONSULTA,
            'activo' => false,
        ]);

        $this->actingAs($administrador)
            ->get('/administracion/usuarios?buscar=ana&rol=comercial&estado=activo')
            ->assertOk()
            ->assertSee('Ana Comercial')
            ->assertDontSee('Ana Consulta')
            ->assertViewHas('usuarios', fn ($usuarios): bool => $usuarios->total() === 1);

        Usuario::factory()->count(16)->create([
            'nombre' => 'Usuario paginado',
            'rol' => Usuario::ROL_CONSULTA,
            'activo' => true,
        ]);

        $this->get('/administracion/usuarios?buscar=Usuario+paginado&rol=consulta&estado=activo')
            ->assertOk()
            ->assertSee('Página 1 de 2')
            ->assertSee('buscar=Usuario%20paginado', false)
            ->assertSee('rol=consulta', false)
            ->assertSee('estado=activo', false);
    }

    public function test_crear_y_editar_son_pantallas_dedicadas_solo_para_administradores(): void
    {
        $administrador = Usuario::factory()->create(['rol' => Usuario::ROL_ADMINISTRADOR]);
        $usuario = Usuario::factory()->create(['rol' => Usuario::ROL_COMERCIAL]);

        $this->actingAs($administrador)
            ->get(route('administracion.usuarios.crear'))
            ->assertOk()
            ->assertSee('action="'.route('administracion.usuarios.guardar').'"', false);

        $this->get(route('administracion.usuarios.editar', $usuario))
            ->assertOk()
            ->assertSee('action="'.route('administracion.usuarios.actualizar', $usuario).'"', false);

        $this->get(route('administracion.usuarios.listado'))
            ->assertOk()
            ->assertDontSee('<form class="formulario-administrativo formulario-dos-columnas" method="POST" action="'.route('administracion.usuarios.guardar').'"', false)
            ->assertDontSee('<form class="formulario-administrativo formulario-dos-columnas" method="POST" action="'.route('administracion.usuarios.actualizar', $usuario).'"', false);

        $comercial = Usuario::factory()->create(['rol' => Usuario::ROL_COMERCIAL]);
        $this->actingAs($comercial)->get(route('administracion.usuarios.crear'))->assertForbidden();
        $this->get(route('administracion.usuarios.editar', $usuario))->assertForbidden();
    }

    public function test_el_listado_oculta_el_formulario_de_estado_del_usuario_autenticado(): void
    {
        $administrador = Usuario::factory()->create(['rol' => Usuario::ROL_ADMINISTRADOR]);
        $otro = Usuario::factory()->create(['rol' => Usuario::ROL_COMERCIAL]);

        $this->actingAs($administrador)->get('/administracion/usuarios')
            ->assertOk()
            ->assertDontSee('action="'.route('administracion.usuarios.estado', $administrador).'"', false)
            ->assertSee('action="'.route('administracion.usuarios.estado', $otro).'"', false);
    }

    public function test_dos_transacciones_superpuestas_conservan_un_administrador_activo(): void
    {
        // SQLite WAL permite confirmar otra escritura mientras la primera transacción lee.
        // MySQL usa FOR UPDATE; no se intenta intercalar aquí una escritura sobre sus bloqueos.
        $archivo = tempnam(sys_get_temp_dir(), 'administradores-');
        $conexionOriginal = DB::getDefaultConnection();
        $configuracion = array_replace(config('database.connections.sqlite'), [
            'database' => $archivo,
            'url' => null,
            'journal_mode' => 'WAL',
        ]);
        config([
            'database.connections.administracion_principal' => $configuracion,
            'database.connections.administracion_paralela' => $configuracion,
        ]);

        try {
            DB::setDefaultConnection('administracion_principal');
            Artisan::call('migrate', ['--database' => 'administracion_principal', '--force' => true]);
            $primero = Usuario::factory()->create(['rol' => Usuario::ROL_ADMINISTRADOR]);
            $segundo = Usuario::factory()->create(['rol' => Usuario::ROL_ADMINISTRADOR]);
            $servicio = app(ServicioAdministracionUsuarios::class);
            $intercalada = false;

            DB::listen(function (QueryExecuted $consulta) use ($servicio, $primero, &$intercalada): void {
                if ($intercalada || $consulta->connectionName !== 'administracion_principal'
                    || ! str_starts_with($consulta->sql, 'select * from "usuarios" where "rol"')) {
                    return;
                }

                $intercalada = true;
                DB::setDefaultConnection('administracion_paralela');

                try {
                    $servicio->actualizar($primero, $primero, ['rol' => Usuario::ROL_CONSULTA]);
                } finally {
                    DB::setDefaultConnection('administracion_principal');
                }
            });

            try {
                $servicio->cambiarEstado($primero, $segundo, false);
                $this->fail('La segunda transición debe preservar al último administrador activo.');
            } catch (ValidationException $excepcion) {
                $this->assertArrayHasKey('usuario', $excepcion->errors());
            }

            $this->assertTrue($intercalada);
            $this->assertSame(Usuario::ROL_CONSULTA, $primero->fresh()->rol);
            $this->assertTrue($segundo->fresh()->activo);
            $this->assertSame(1, Usuario::where('rol', Usuario::ROL_ADMINISTRADOR)->where('activo', true)->count());
        } finally {
            DB::setDefaultConnection($conexionOriginal);
            DB::purge('administracion_principal');
            DB::purge('administracion_paralela');

            foreach ([$archivo, $archivo.'-wal', $archivo.'-shm'] as $temporal) {
                if (is_file($temporal)) {
                    unlink($temporal);
                }
            }
        }
    }
}
