<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavegacionTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrador_encuentra_el_menu_en_los_modulos(): void
    {
        $this->actingAs(Usuario::factory()->create(['role' => Usuario::ROL_ADMINISTRADOR]));

        foreach (['/panel', '/clientes', '/catalogo', '/inventario', '/cotizaciones', '/ventas', '/administracion/usuarios'] as $ruta) {
            $this->get($ruta)->assertOk()
                ->assertSee('aria-label="Navegación principal"', false)
                ->assertSee('href="'.route('cotizaciones.listado').'"', false)
                ->assertSee('href="'.route('ventas.listado').'"', false)
                ->assertSee('href="'.route('inventario.listado').'"', false)
                ->assertSee('href="'.route('administracion.usuarios.listado').'"', false)
                ->assertSee('aria-current="page"', false);
        }
    }

    public function test_consulta_solo_recibe_enlaces_autorizados(): void
    {
        $this->actingAs(Usuario::factory()->create(['role' => Usuario::ROL_CONSULTA]));
        $this->get('/panel')->assertOk()
            ->assertSee('href="'.route('cotizaciones.listado').'"', false)
            ->assertSee('href="'.route('ventas.listado').'"', false)
            ->assertDontSee('href="'.route('inventario.listado').'"', false)
            ->assertDontSee('href="'.route('administracion.usuarios.listado').'"', false);
    }
}
