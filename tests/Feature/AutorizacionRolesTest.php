<?php

namespace Tests\Feature;

use App\Models\ArticuloCatalogo;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutorizacionRolesTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_administrador_puede_gestionar_usuarios(): void
    {
        $this->actingAs(Usuario::factory()->create([
            'role' => Usuario::ROL_ADMINISTRADOR,
        ]))
            ->get('/administracion/usuarios')
            ->assertOk()
            ->assertSee('Administración de usuarios');
    }

    public function test_el_comercial_no_puede_gestionar_usuarios(): void
    {
        $this->actingAs(Usuario::factory()->create([
            'role' => Usuario::ROL_COMERCIAL,
        ]))
            ->get('/administracion/usuarios')
            ->assertForbidden();
    }

    public function test_el_usuario_de_consulta_no_puede_gestionar_usuarios(): void
    {
        $this->actingAs(Usuario::factory()->create([
            'role' => Usuario::ROL_CONSULTA,
        ]))
            ->get('/administracion/usuarios')
            ->assertForbidden();
    }

    public function test_administrador_y_comercial_pueden_acceder_a_modulos_operativos(): void
    {
        $rutas = [
            '/panel',
            '/clientes',
            '/catalogo',
            '/inventario',
            '/cotizaciones',
            '/ventas',
            '/reportes/cotizaciones',
            '/reportes/ventas',
        ];

        foreach ([Usuario::ROL_ADMINISTRADOR, Usuario::ROL_COMERCIAL] as $rol) {
            $this->actingAs(Usuario::factory()->create([
                'role' => $rol,
            ]));

            foreach ($rutas as $ruta) {
                $this->get($ruta)->assertOk();
            }
        }
    }

    public function test_consulta_solo_accede_a_modulos_de_lectura_autorizados(): void
    {
        $this->actingAs(Usuario::factory()->create([
            'role' => Usuario::ROL_CONSULTA,
        ]));

        foreach ([
            '/panel',
            '/cotizaciones',
            '/ventas',
            '/reportes/cotizaciones',
            '/reportes/ventas',
        ] as $ruta) {
            $this->get($ruta)->assertOk();
        }

        foreach ([
            '/clientes',
            '/catalogo',
            '/inventario',
            '/administracion/usuarios',
        ] as $ruta) {
            $this->get($ruta)->assertForbidden();
        }
    }

    public function test_consulta_no_puede_escribir_en_modulos_operativos_por_ruta_directa(): void
    {
        $usuario = Usuario::factory()->create([
            'role' => Usuario::ROL_CONSULTA,
        ]);
        $cliente = Cliente::create([
            'type' => 'moral',
            'name' => 'Cliente de permisos',
            'rfc' => 'PER010101AA1',
            'email' => 'permisos@example.test',
            'phone' => '9610000000',
            'address' => 'Domicilio de prueba',
            'postal_code' => '29000',
        ]);
        $cotizacion = Cotizacion::create([
            'folio' => 'COT-PERMISOS',
            'customer_id' => $cliente->id,
            'user_id' => $usuario->id,
            'status' => 'accepted',
            'total' => 100,
        ]);
        $articulo = ArticuloCatalogo::create([
            'type' => 'service',
            'name' => 'Servicio de permisos',
            'code' => 'SER-PERMISOS',
            'unit' => 'servicio',
            'price' => 100,
            'stock' => 0,
        ]);

        $this->actingAs($usuario);

        $this->post('/clientes')->assertForbidden();
        $this->put("/clientes/{$cliente->id}")->assertForbidden();
        $this->post('/catalogo')->assertForbidden();
        $this->patch("/catalogo/{$articulo->id}/estado")->assertForbidden();
        $this->post('/inventario/categorias')->assertForbidden();
        $this->post('/inventario/series')->assertForbidden();
        $this->post("/cotizaciones/{$cotizacion->id}/venta")->assertForbidden();
        $this->post('/administracion/usuarios')->assertForbidden();
    }
}
