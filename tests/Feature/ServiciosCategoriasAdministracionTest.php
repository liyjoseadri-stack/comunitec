<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Servicio;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiciosCategoriasAdministracionTest extends TestCase
{
    use RefreshDatabase;

    public function test_servicios_tienen_pantallas_separadas_para_listar_crear_consultar_y_editar(): void
    {
        $usuario = Usuario::factory()->create(['rol' => Usuario::ROL_COMERCIAL]);
        $categoria = Categoria::create(['nombre' => 'Soporte']);
        $servicio = $this->servicio($categoria);

        $this->actingAs($usuario)
            ->get(route('inventario.servicios.listado'))
            ->assertOk()
            ->assertSee(route('inventario.servicios.crear'), false)
            ->assertDontSee('action="'.route('inventario.servicios.guardar').'"', false);

        $this->get(route('inventario.servicios.crear'))
            ->assertOk()
            ->assertSee('Registrar servicio')
            ->assertSee('action="'.route('inventario.servicios.guardar').'"', false);

        $this->get(route('inventario.servicios.detalle', $servicio))
            ->assertOk()
            ->assertSee('Información del servicio')
            ->assertSee(route('inventario.servicios.editar', $servicio), false)
            ->assertDontSee('action="'.route('inventario.servicios.actualizar', $servicio).'"', false);

        $this->get(route('inventario.servicios.editar', $servicio))
            ->assertOk()
            ->assertSee('Editar servicio')
            ->assertSee('action="'.route('inventario.servicios.actualizar', $servicio).'"', false);
    }

    public function test_servicio_conserva_su_categoria_inactiva_al_editar(): void
    {
        $usuario = Usuario::factory()->create(['rol' => Usuario::ROL_COMERCIAL]);
        $categoria = Categoria::create(['nombre' => 'Servicios heredados', 'activo' => false]);
        $servicio = $this->servicio($categoria);

        $this->actingAs($usuario)
            ->get(route('inventario.servicios.editar', $servicio))
            ->assertOk()
            ->assertSee('Servicios heredados');

        $this->put(route('inventario.servicios.actualizar', $servicio), [
            'nombre' => 'Mantenimiento actualizado',
            'codigo' => $servicio->codigo,
            'categoria_id' => $categoria->id,
            'unidad' => 'servicio',
            'precio' => 1350,
        ])->assertRedirect(route('inventario.servicios.detalle', $servicio));

        $this->assertDatabaseHas('servicios', [
            'id' => $servicio->id,
            'categoria_id' => $categoria->id,
            'nombre' => 'Mantenimiento actualizado',
        ]);
    }

    public function test_categorias_tienen_pantallas_separadas_y_el_detalle_conserva_sus_relaciones(): void
    {
        $usuario = Usuario::factory()->create(['rol' => Usuario::ROL_COMERCIAL]);
        $categoria = Categoria::create(['nombre' => 'Infraestructura']);
        Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Rack',
            'codigo' => 'RACK-01',
            'unidad' => 'pieza',
            'precio' => 5000,
            'existencias' => 2,
        ]);
        $servicio = $this->servicio($categoria);

        $this->actingAs($usuario)
            ->get(route('inventario.categorias.listado'))
            ->assertOk()
            ->assertSee(route('inventario.categorias.crear'), false)
            ->assertDontSee('action="'.route('inventario.categorias.guardar').'"', false);

        $this->get(route('inventario.categorias.crear'))
            ->assertOk()
            ->assertSee('Registrar categoría')
            ->assertSee('action="'.route('inventario.categorias.guardar').'"', false);

        $this->get(route('inventario.categorias.detalle', $categoria))
            ->assertOk()
            ->assertSee('Información de la categoría')
            ->assertSee('Rack')
            ->assertSee($servicio->nombre)
            ->assertSee(route('inventario.categorias.editar', $categoria), false)
            ->assertDontSee('action="'.route('inventario.categorias.actualizar', $categoria).'"', false);

        $this->get(route('inventario.categorias.editar', $categoria))
            ->assertOk()
            ->assertSee('Editar categoría')
            ->assertSee('action="'.route('inventario.categorias.actualizar', $categoria).'"', false);
    }

    public function test_guardar_y_actualizar_redirigen_a_la_consulta_del_registro(): void
    {
        $usuario = Usuario::factory()->create(['rol' => Usuario::ROL_COMERCIAL]);
        $categoria = Categoria::create(['nombre' => 'Soporte']);

        $this->actingAs($usuario)->post(route('inventario.servicios.guardar'), [
            'nombre' => 'Instalación de red',
            'codigo' => 'SER-RED',
            'categoria_id' => $categoria->id,
            'unidad' => 'servicio',
            'precio' => 1800,
        ])->assertRedirect(route('inventario.servicios.detalle', Servicio::firstOrFail()));

        $nuevaCategoria = 'Videovigilancia';
        $respuesta = $this->post(route('inventario.categorias.guardar'), ['nombre' => $nuevaCategoria]);
        $registrada = Categoria::where('nombre', $nuevaCategoria)->firstOrFail();
        $respuesta->assertRedirect(route('inventario.categorias.detalle', $registrada));

        $this->put(route('inventario.categorias.actualizar', $registrada), ['nombre' => 'CCTV'])
            ->assertRedirect(route('inventario.categorias.detalle', $registrada));
    }

    public function test_las_nuevas_pantallas_respetan_permisos_y_validaciones(): void
    {
        $categoriaInactiva = Categoria::create(['nombre' => 'Archivada', 'activo' => false]);

        $this->get(route('inventario.servicios.crear'))->assertRedirect(route('login'));

        $this->actingAs(Usuario::factory()->create(['rol' => Usuario::ROL_CONSULTA]))
            ->get(route('inventario.categorias.crear'))
            ->assertForbidden();

        $this->actingAs(Usuario::factory()->create(['rol' => Usuario::ROL_COMERCIAL]))
            ->post(route('inventario.servicios.guardar'), [
                'nombre' => 'Servicio inválido',
                'codigo' => 'SER-INVALIDO',
                'categoria_id' => $categoriaInactiva->id,
                'unidad' => 'servicio',
                'precio' => 100,
            ])
            ->assertSessionHasErrors('categoria_id');

        $this->post(route('inventario.categorias.guardar'), ['nombre' => 'Archivada'])
            ->assertSessionHasErrors('nombre');
    }

    private function servicio(Categoria $categoria): Servicio
    {
        return Servicio::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Mantenimiento preventivo',
            'codigo' => 'SER-MANT',
            'unidad' => 'servicio',
            'precio' => 1200,
        ]);
    }
}
