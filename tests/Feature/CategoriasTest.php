<?php

namespace Tests\Feature;

use App\Models\ArticuloCatalogo;
use App\Models\Categoria;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoriasTest extends TestCase
{
    use RefreshDatabase;

    public function test_comercial_puede_crear_buscar_y_editar_una_categoria(): void
    {
        $usuario = Usuario::factory()->create(['rol' => Usuario::ROL_COMERCIAL]);

        $this->actingAs($usuario)->post('/inventario/categorias', [
            'nombre' => 'Videovigilancia',
        ])->assertRedirect();
        $categoria = Categoria::sole();

        $this->get('/inventario/categorias?buscar=Video')
            ->assertOk()
            ->assertSee('Videovigilancia');
        $this->put("/inventario/categorias/{$categoria->id}", [
            'nombre' => 'CCTV',
        ])->assertRedirect();

        $this->assertDatabaseHas('categorias', ['id' => $categoria->id, 'nombre' => 'CCTV']);
    }

    public function test_detalle_muestra_productos_de_la_categoria(): void
    {
        $usuario = Usuario::factory()->create(['rol' => Usuario::ROL_COMERCIAL]);
        $categoria = Categoria::create(['nombre' => 'Redes']);
        ArticuloCatalogo::create([
            'categoria_id' => $categoria->id, 'tipo' => 'producto', 'nombre' => 'Switch',
            'codigo' => 'SW-CAT', 'unidad' => 'pieza', 'precio' => 1000, 'existencias' => 3,
        ]);

        $this->actingAs($usuario)->get("/inventario/categorias/{$categoria->id}")
            ->assertOk()->assertSee('Switch')->assertSee('SW-CAT');
    }

    public function test_categoria_con_historial_se_desactiva_sin_eliminarse(): void
    {
        $usuario = Usuario::factory()->create(['rol' => Usuario::ROL_COMERCIAL]);
        $categoria = Categoria::create(['nombre' => 'Accesorios']);

        $this->actingAs($usuario)
            ->patch("/inventario/categorias/{$categoria->id}/estado")
            ->assertRedirect();

        $this->assertDatabaseHas('categorias', ['id' => $categoria->id, 'activo' => false]);
        $this->patch("/inventario/categorias/{$categoria->id}/estado")->assertRedirect();
        $this->assertTrue($categoria->fresh()->activo);
    }

    public function test_consulta_no_puede_modificar_categorias(): void
    {
        $usuario = Usuario::factory()->create(['rol' => Usuario::ROL_CONSULTA]);

        $this->actingAs($usuario)->post('/inventario/categorias', [
            'nombre' => 'Prohibida',
        ])->assertForbidden();
    }
}
